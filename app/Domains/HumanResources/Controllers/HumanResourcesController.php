<?php

namespace App\Domains\HumanResources\Controllers;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HumanResourcesController extends Controller
{
    public function __construct(
        protected LeaveManagementService $leaveManagementService
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $actor = $user?->staffMember?->load(['department', 'manager']);
        $selectedDepartmentId = $request->integer('department_id') ?: null;
        $today = CarbonImmutable::today(config('app.timezone'));
        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $canManageManagerLeave = (bool) $user?->can('domain.leave.manage');
        $canManageHrLeave = (bool) $user?->can('domain.human-resources.manage');
        $canViewHrBoard = (bool) $user?->can('domain.human-resources.view') || $canManageHrLeave;

        $departments = StaffDepartment::query()
            ->withCount('staffMembers')
            ->orderBy('name')
            ->get()
            ->map(fn (StaffDepartment $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'staff_count' => $department->staff_members_count,
            ]);

        $staffMembers = StaffMember::query()
            ->with(['department', 'manager'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $managerOptions = StaffMember::query()
            ->select('id', 'first_name', 'last_name', 'department_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (StaffMember $staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'department_id' => $staff->department_id,
            ]);

        $organizationLeave = $this->leaveManagementService->organizationSummary();
        $staffDirectory = $staffMembers
            ->when($selectedDepartmentId, fn ($collection) => $collection->where('department_id', $selectedDepartmentId))
            ->map(fn (StaffMember $staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'email' => $staff->email,
                'employee_number' => $staff->employee_number,
                'status' => $staff->status,
                'employment_type' => $staff->is_intern ? 'Contract' : 'Permanent',
                'position' => $this->positionLabel($staff),
                'avatar_initials' => $this->initials($staff),
                'department_id' => $staff->department_id,
                'department_name' => $staff->department?->name,
                'manager_name' => $staff->manager
                    ? trim($staff->manager->first_name.' '.$staff->manager->last_name)
                    : null,
            ])
            ->values();

        $presentToday = StaffAttendanceRecord::query()
            ->whereDate('attendance_date', $today->toDateString())
            ->whereNotNull('clock_in_at')
            ->distinct('staff_member_id')
            ->count('staff_member_id');
        $onLeaveToday = LeaveRequest::query()
            ->where('status', 'hr_approved')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->distinct('staff_member_id')
            ->count('staff_member_id');
        $newEmployees = StaffMember::query()
            ->whereDate('start_date', '>=', $today->subDays(30)->toDateString())
            ->count();
        $pendingApprovals = LeaveRequest::query()
            ->whereIn('status', ['submitted', 'manager_approved'])
            ->count();
        $monthLeaveDays = (float) LeaveRequest::query()
            ->where('status', 'hr_approved')
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->sum('total_days');
        $availableLeaveDays = (float) ($organizationLeave['totals']['annual_available'] ?? 0);
        $activeStaff = $staffMembers->where('status', 'active')->count();
        $attendanceRate = $activeStaff > 0 ? round(($presentToday / max($activeStaff, 1)) * 100) : 0;

        $headcountTrend = collect(range(7, 0))
            ->map(function (int $monthsAgo) use ($staffMembers, $today) {
                $point = $today->subMonths($monthsAgo)->endOfMonth();

                return [
                    'label' => $point->format('M'),
                    'staff' => $staffMembers
                        ->filter(fn (StaffMember $staff) => ! $staff->start_date || $staff->start_date->lte($point))
                        ->count(),
                ];
            })
            ->values();

        $attendanceTrend = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($today) {
                $date = $today->subDays($daysAgo);

                return [
                    'label' => $date->format('D'),
                    'present' => StaffAttendanceRecord::query()
                        ->whereDate('attendance_date', $date->toDateString())
                        ->whereNotNull('clock_in_at')
                        ->distinct('staff_member_id')
                        ->count('staff_member_id'),
                ];
            })
            ->values();

        $leaveCalendar = LeaveRequest::query()
            ->with('staffMember')
            ->whereIn('status', ['submitted', 'manager_approved', 'hr_approved'])
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->orderBy('start_date')
            ->get()
            ->map(fn (LeaveRequest $leave) => [
                'id' => $leave->id,
                'day' => (int) $leave->start_date?->format('j'),
                'type' => $leave->leave_type ?? 'annual',
                'status' => $leave->status,
                'label' => trim(($leave->staffMember?->first_name ?? '').' '.($leave->staffMember?->last_name ?? '')),
            ])
            ->values();

        $holidays = AttendanceRegister::query()
            ->where('is_holiday', true)
            ->whereDate('attendance_date', '>=', $monthStart->toDateString())
            ->orderBy('attendance_date')
            ->get()
            ->unique(fn (AttendanceRegister $register) => $register->attendance_date?->format('Y-m-d').$register->holiday_reason)
            ->take(3)
            ->map(fn (AttendanceRegister $register) => [
                'date' => $register->attendance_date?->format('Y-m-d'),
                'label' => $register->holiday_reason ?: 'Holiday',
                'days_until' => $register->attendance_date
                    ? max(0, $today->diffInDays(CarbonImmutable::parse($register->attendance_date), false))
                    : null,
            ])
            ->values();

        $pendingLeaveApprovals = collect();

        if ($canManageManagerLeave && $actor) {
            $pendingLeaveApprovals = LeaveRequest::query()
                ->with(['staffMember.department', 'manager'])
                ->where('manager_id', $actor->id)
                ->where('status', 'submitted');

            if ($selectedDepartmentId) {
                $pendingLeaveApprovals->whereHas('staffMember', fn ($query) => $query->where('department_id', $selectedDepartmentId));
            }
        } elseif ($canViewHrBoard) {
            $pendingLeaveApprovals = LeaveRequest::query()
                ->with(['staffMember.department', 'manager'])
                ->when(
                    $canManageHrLeave,
                    fn ($query) => $query->where('status', 'manager_approved'),
                    fn ($query) => $query->whereRaw('1 = 0')
                );

            if ($selectedDepartmentId) {
                $pendingLeaveApprovals->whereHas('staffMember', fn ($query) => $query->where('department_id', $selectedDepartmentId));
            }
        }

        return Inertia::render('HumanResources/Dashboard', [
            'stats' => [
                'totalStaff' => $staffMembers->count(),
                'activeStaff' => $activeStaff,
                'inactiveStaff' => $staffMembers->where('status', 'inactive')->count(),
                'presentToday' => $presentToday,
                'onLeaveToday' => $onLeaveToday,
                'pendingApprovals' => $pendingApprovals,
                'pendingManager' => LeaveRequest::where('status', 'submitted')->count(),
                'pendingHr' => LeaveRequest::where('status', 'manager_approved')->count(),
                'approved' => LeaveRequest::where('status', 'hr_approved')->count(),
                'newEmployees' => $newEmployees,
                'attendanceRate' => $attendanceRate,
                'monthLeaveDays' => $monthLeaveDays,
                'availableLeaveDays' => $availableLeaveDays,
            ],
            'analytics' => [
                'headcountTrend' => $headcountTrend,
                'attendanceTrend' => $attendanceTrend,
                'departmentDistribution' => $departments
                    ->map(fn (array $department) => [
                        'name' => $department['name'],
                        'staff' => $department['staff_count'],
                    ])
                    ->values(),
                'employmentTypes' => [
                    ['name' => 'Permanent', 'value' => $staffMembers->where('is_intern', false)->count()],
                    ['name' => 'Contract', 'value' => $staffMembers->where('is_intern', true)->count()],
                ],
                'staffMix' => [
                    ['name' => 'Managers', 'value' => $staffMembers->where('is_manager', true)->count()],
                    ['name' => 'Interns', 'value' => $staffMembers->where('is_intern', true)->count()],
                    ['name' => 'Staff', 'value' => $staffMembers->where('is_manager', false)->where('is_intern', false)->count()],
                ],
            ],
            'workforce' => [
                'present' => $presentToday,
                'onLeave' => $onLeaveToday,
                'absent' => max(0, $activeStaff - $presentToday - $onLeaveToday),
                'pendingApprovals' => $pendingApprovals,
                'newEmployees' => $newEmployees,
            ],
            'leaveCalendar' => [
                'monthLabel' => $today->format('F Y'),
                'today' => (int) $today->format('j'),
                'events' => $leaveCalendar,
                'holidays' => $holidays,
            ],
            'departments' => $departments,
            'managers' => $managerOptions,
            'leaveSummary' => $organizationLeave,
            'staffDirectory' => $staffDirectory,
            'pendingLeaveApprovals' => $pendingLeaveApprovals instanceof \Illuminate\Database\Eloquent\Builder
                ? $pendingLeaveApprovals
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn (LeaveRequest $leave) => $this->leaveManagementService->mapLeave($leave))
                    ->values()
                : [],
            'selectedDepartmentId' => $selectedDepartmentId,
            'canManageManagerLeave' => $canManageManagerLeave && (bool) $actor,
            'canManageHrLeave' => $canManageHrLeave,
        ]);
    }

    private function positionLabel(StaffMember $staff): string
    {
        if ($staff->is_ceo) {
            return 'Chief Executive';
        }

        if ($staff->is_board_member) {
            return 'Board Member';
        }

        if ($staff->is_manager) {
            return 'Manager';
        }

        if ($staff->is_intern) {
            return 'Intern';
        }

        return 'Staff Member';
    }

    private function initials(StaffMember $staff): string
    {
        return strtoupper(substr($staff->first_name, 0, 1).substr($staff->last_name, 0, 1));
    }
}
