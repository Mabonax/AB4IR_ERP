<?php

namespace App\Domains\Leave\Services;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LeaveManagementService
{
    public const ANNUAL_ACCRUAL_PER_MONTH = 1.25;

    public const DEFAULT_SICK_ALLOWANCE = 10.0;

    public function currentPeriod(?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();
        $fyStart = Carbon::create($now->year, 3, 1);

        if ($now->lt($fyStart)) {
            $fyStart = $fyStart->subYear();
        }

        $fyEnd = (clone $fyStart)->addYear()->subDay();

        return [
            'start' => $fyStart,
            'end' => $fyEnd,
        ];
    }

    public function calculateWorkingDays(Carbon $start, Carbon $end): float
    {
        $days = 0;

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            if (! $date->isWeekend()) {
                $days++;
            }
        }

        return (float) $days;
    }

    public function leaveTypeOptions(): array
    {
        return [
            ['value' => 'annual', 'label' => 'Annual Leave'],
            ['value' => 'sick', 'label' => 'Sick Leave'],
        ];
    }

    public function createRequest(StaffMember $staff, array $data): LeaveRequest
    {
        if (! $staff->manager_id) {
            throw ValidationException::withMessages([
                'manager_id' => 'A reporting manager must be assigned before leave can be requested.',
            ]);
        }

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $leaveType = $data['leave_type'];
        $totalDays = $this->calculateWorkingDays($start, $end);

        if ($totalDays <= 0) {
            throw ValidationException::withMessages([
                'end_date' => 'The selected period does not contain any working days.',
            ]);
        }

        $balance = $this->summarizeStaff($staff);
        $available = $leaveType === 'sick'
            ? $balance['sick']['available']
            : $balance['annual']['available'];

        if ($totalDays > $available) {
            throw ValidationException::withMessages([
                'end_date' => sprintf(
                    'Insufficient %s balance.',
                    $leaveType === 'sick' ? 'sick leave' : 'annual leave'
                ),
            ]);
        }

        return LeaveRequest::query()->create([
            'staff_member_id' => $staff->id,
            'manager_id' => $staff->manager_id,
            'leave_type' => $leaveType,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function managerApprove(StaffMember $actor, LeaveRequest $leave, ?string $comment = null): LeaveRequest
    {
        $this->assertManagerOwnership($actor, $leave);

        if ($leave->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => 'Leave request is not awaiting manager approval.',
            ]);
        }

        $leave->update([
            'status' => 'manager_approved',
            'manager_comment' => $comment,
            'manager_approved_at' => now(),
        ]);

        return $leave->fresh(['staffMember.department', 'manager']);
    }

    public function managerReject(StaffMember $actor, LeaveRequest $leave, ?string $comment = null): LeaveRequest
    {
        $this->assertManagerOwnership($actor, $leave);

        if ($leave->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => 'Leave request is not awaiting manager approval.',
            ]);
        }

        $leave->update([
            'status' => 'manager_rejected',
            'manager_comment' => $comment,
        ]);

        return $leave->fresh(['staffMember.department', 'manager']);
    }

    public function hrApprove(LeaveRequest $leave, ?string $comment = null): LeaveRequest
    {
        if ($leave->status !== 'manager_approved') {
            throw ValidationException::withMessages([
                'status' => 'Leave request is not awaiting HR approval.',
            ]);
        }

        $leave->update([
            'status' => 'hr_approved',
            'hr_comment' => $comment,
            'hr_approved_at' => now(),
        ]);

        return $leave->fresh(['staffMember.department', 'manager']);
    }

    public function hrReject(LeaveRequest $leave, ?string $comment = null): LeaveRequest
    {
        if ($leave->status !== 'manager_approved') {
            throw ValidationException::withMessages([
                'status' => 'Leave request is not awaiting HR approval.',
            ]);
        }

        $leave->update([
            'status' => 'hr_rejected',
            'hr_comment' => $comment,
        ]);

        return $leave->fresh(['staffMember.department', 'manager']);
    }

    public function summarizeStaff(StaffMember $staff, ?Carbon $now = null): array
    {
        $period = $this->currentPeriod($now);
        $periodStart = $period['start']->copy();
        $periodEnd = $period['end']->copy();
        $now = $now ? $now->copy() : Carbon::now();

        $startDate = $staff->start_date ? Carbon::parse($staff->start_date) : $periodStart->copy();
        $accrualStart = $startDate->gt($periodStart) ? $startDate->copy() : $periodStart->copy();

        if ($accrualStart->gt($now)) {
            $annualAccrued = 0.0;
        } else {
            $annualAccrued = round(
                $accrualStart->copy()->startOfMonth()->diffInMonths($now->copy()->startOfMonth())
                * self::ANNUAL_ACCRUAL_PER_MONTH,
                2
            );
        }

        $approved = LeaveRequest::query()
            ->where('staff_member_id', $staff->id)
            ->where('status', 'hr_approved')
            ->whereBetween('start_date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
            ->get();

        $annualTaken = round((float) $approved->where('leave_type', 'annual')->sum('total_days'), 2);
        $sickTaken = round((float) $approved->where('leave_type', 'sick')->sum('total_days'), 2);
        $pending = LeaveRequest::query()
            ->where('staff_member_id', $staff->id)
            ->whereIn('status', ['submitted', 'manager_approved'])
            ->get();

        return [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'annual' => [
                'accrued' => $annualAccrued,
                'taken' => $annualTaken,
                'available' => max(round($annualAccrued - $annualTaken, 2), 0),
            ],
            'sick' => [
                'entitlement' => self::DEFAULT_SICK_ALLOWANCE,
                'taken' => $sickTaken,
                'available' => max(round(self::DEFAULT_SICK_ALLOWANCE - $sickTaken, 2), 0),
            ],
            'pending' => [
                'count' => $pending->count(),
                'days' => round((float) $pending->sum('total_days'), 2),
            ],
        ];
    }

    public function mapLeave(LeaveRequest $leave): array
    {
        return [
            'id' => $leave->id,
            'staff_member_id' => $leave->staff_member_id,
            'staff_member_name' => $leave->staffMember
                ? trim($leave->staffMember->first_name.' '.$leave->staffMember->last_name)
                : null,
            'department_name' => $leave->staffMember?->department?->name,
            'manager_id' => $leave->manager_id,
            'manager_name' => $leave->manager
                ? trim($leave->manager->first_name.' '.$leave->manager->last_name)
                : null,
            'leave_type' => $leave->leave_type,
            'leave_type_label' => $leave->leave_type === 'sick' ? 'Sick Leave' : 'Annual Leave',
            'start_date' => $leave->start_date?->format('Y-m-d'),
            'end_date' => $leave->end_date?->format('Y-m-d'),
            'total_days' => (float) $leave->total_days,
            'status' => $leave->status,
            'reason' => $leave->reason,
            'manager_comment' => $leave->manager_comment,
            'hr_comment' => $leave->hr_comment,
            'balance_impact' => [
                'bucket' => $leave->leave_type,
                'days' => (float) $leave->total_days,
            ],
        ];
    }

    public function teamSummaryForManager(StaffMember $manager): array
    {
        $reports = $manager->directReports()
            ->with(['department'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return $this->summariesForStaffCollection($reports);
    }

    public function summariesForStaffCollection(iterable $staffCollection): array
    {
        return collect($staffCollection)
            ->map(function (StaffMember $staff) {
                $summary = $this->summarizeStaff($staff);

                return [
                    'staff_id' => $staff->id,
                    'staff_name' => trim($staff->first_name.' '.$staff->last_name),
                    'department_name' => $staff->department?->name,
                    'manager_name' => $staff->manager
                        ? trim($staff->manager->first_name.' '.$staff->manager->last_name)
                        : null,
                    'leave_account' => $summary,
                ];
            })
            ->values()
            ->all();
    }

    public function organizationSummary(): array
    {
        $staffMembers = StaffMember::query()
            ->with(['department', 'manager'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $summaries = $this->summariesForStaffCollection($staffMembers);

        return [
            'totals' => [
                'annual_taken' => round((float) collect($summaries)->sum(fn (array $item) => $item['leave_account']['annual']['taken']), 2),
                'annual_available' => round((float) collect($summaries)->sum(fn (array $item) => $item['leave_account']['annual']['available']), 2),
                'sick_taken' => round((float) collect($summaries)->sum(fn (array $item) => $item['leave_account']['sick']['taken']), 2),
                'sick_available' => round((float) collect($summaries)->sum(fn (array $item) => $item['leave_account']['sick']['available']), 2),
            ],
            'staff' => $summaries,
        ];
    }

    public function managerDashboardSummary(?StaffMember $manager): array
    {
        if (! $manager) {
            return [
                'pending_approvals' => 0,
                'team_members' => 0,
                'team_annual_available' => 0,
                'team_sick_available' => 0,
                'team' => [],
            ];
        }

        $team = $this->teamSummaryForManager($manager);

        return [
            'pending_approvals' => LeaveRequest::query()
                ->where('manager_id', $manager->id)
                ->where('status', 'submitted')
                ->count(),
            'team_members' => count($team),
            'team_annual_available' => round((float) collect($team)->sum(fn (array $item) => $item['leave_account']['annual']['available']), 2),
            'team_sick_available' => round((float) collect($team)->sum(fn (array $item) => $item['leave_account']['sick']['available']), 2),
            'team' => $team,
        ];
    }

    public function legacyBalance(StaffMember $staff): array
    {
        $summary = $this->summarizeStaff($staff);

        return [
            'accrued' => $summary['annual']['accrued'],
            'used' => $summary['annual']['taken'],
            'available' => $summary['annual']['available'],
            'period_start' => $summary['period_start'],
            'period_end' => $summary['period_end'],
        ];
    }

    protected function assertManagerOwnership(StaffMember $actor, LeaveRequest $leave): void
    {
        if ((int) $leave->manager_id !== (int) $actor->id) {
            throw new AuthorizationException('You are not allowed to action this leave request.');
        }
    }
}
