<?php

namespace App\Domains\HumanResources\Controllers;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HumanResourcesController extends Controller
{
    public function __construct(
        protected LeaveManagementService $leaveManagementService
    ) {}

    public function dashboard(Request $request)
    {
        $selectedDepartmentId = $request->integer('department_id') ?: null;

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
        $staffDirectory = StaffMember::query()
            ->with(['department', 'manager'])
            ->when($selectedDepartmentId, fn ($query) => $query->where('department_id', $selectedDepartmentId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (StaffMember $staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'email' => $staff->email,
                'employee_number' => $staff->employee_number,
                'status' => $staff->status,
                'department_id' => $staff->department_id,
                'department_name' => $staff->department?->name,
                'manager_name' => $staff->manager
                    ? trim($staff->manager->first_name.' '.$staff->manager->last_name)
                    : null,
            ])
            ->values();

        return Inertia::render('HumanResources/Dashboard', [
            'stats' => [
                'totalStaff' => StaffMember::count(),
                'activeStaff' => StaffMember::where('status', 'active')->count(),
                'inactiveStaff' => StaffMember::where('status', 'inactive')->count(),
                'pendingManager' => LeaveRequest::where('status', 'submitted')->count(),
                'pendingHr' => LeaveRequest::where('status', 'manager_approved')->count(),
                'approved' => LeaveRequest::where('status', 'hr_approved')->count(),
            ],
            'departments' => $departments,
            'managers' => $managerOptions,
            'leaveSummary' => $organizationLeave,
            'staffDirectory' => $staffDirectory,
            'selectedDepartmentId' => $selectedDepartmentId,
        ]);
    }
}
