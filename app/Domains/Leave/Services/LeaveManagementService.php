<?php

namespace App\Domains\Leave\Services;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Notifications\LeaveRequestNotification;
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

        $this->assertNoOverlappingLeaveRequest($staff, $start, $end);

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

        $leave = LeaveRequest::query()->create([
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

        $leave = $leave->fresh(['staffMember.department', 'manager.user']);
        $managerUser = $leave->manager?->user;

        if ($managerUser) {
            $managerUser->notify(new LeaveRequestNotification(
                $leave,
                'New leave request submitted',
                sprintf(
                    '%s submitted %s from %s to %s.',
                    $this->staffName($leave->staffMember),
                    $this->leaveTypeLabel($leave->leave_type),
                    $leave->start_date?->format('Y-m-d'),
                    $leave->end_date?->format('Y-m-d')
                ),
                'submitted'
            ));
        }

        return $leave;
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

        $leave = $leave->fresh(['staffMember.department', 'staffMember.user', 'manager', 'manager.user']);

        $this->notifyRequester(
            $leave,
            'Leave request approved by manager',
            sprintf(
                '%s approved your %s request for %s to %s.',
                $this->staffName($leave->manager),
                $this->leaveTypeLabel($leave->leave_type),
                $leave->start_date?->format('Y-m-d'),
                $leave->end_date?->format('Y-m-d')
            ),
            'manager_approved'
        );

        $this->notifyHrReviewers(
            $leave,
            'Leave request awaiting HR approval',
            sprintf(
                "%s has approved %s's %s request for %s to %s.",
                $this->staffName($leave->manager),
                $this->staffName($leave->staffMember),
                $this->leaveTypeLabel($leave->leave_type),
                $leave->start_date?->format('Y-m-d'),
                $leave->end_date?->format('Y-m-d')
            ),
            'hr_review_required'
        );

        return $leave;
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

        $leave = $leave->fresh(['staffMember.department', 'staffMember.user', 'manager', 'manager.user']);

        $this->notifyRequester(
            $leave,
            'Leave request rejected by manager',
            sprintf(
                '%s rejected your %s request for %s to %s.',
                $this->staffName($leave->manager),
                $this->leaveTypeLabel($leave->leave_type),
                $leave->start_date?->format('Y-m-d'),
                $leave->end_date?->format('Y-m-d')
            ),
            'manager_rejected'
        );

        return $leave;
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

        $leave = $leave->fresh(['staffMember.department', 'staffMember.user', 'manager', 'manager.user']);

        $this->notifyRequester(
            $leave,
            'Leave request approved by HR',
            sprintf(
                'HR approved your %s request for %s to %s.',
                $this->leaveTypeLabel($leave->leave_type),
                $leave->start_date?->format('Y-m-d'),
                $leave->end_date?->format('Y-m-d')
            ),
            'hr_approved'
        );

        return $leave;
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

        $leave = $leave->fresh(['staffMember.department', 'staffMember.user', 'manager', 'manager.user']);

        $this->notifyRequester(
            $leave,
            'Leave request rejected by HR',
            sprintf(
                'HR rejected your %s request for %s to %s.',
                $this->leaveTypeLabel($leave->leave_type),
                $leave->start_date?->format('Y-m-d'),
                $leave->end_date?->format('Y-m-d')
            ),
            'hr_rejected'
        );

        return $leave;
    }

    public function revokeRequest(StaffMember $actor, LeaveRequest $leave): LeaveRequest
    {
        if ((int) $leave->staff_member_id !== (int) $actor->id) {
            throw new AuthorizationException('You are not allowed to revoke this leave request.');
        }

        if (! in_array($leave->status, ['submitted', 'manager_approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending leave requests can be revoked.',
            ]);
        }

        $leave->update([
            'status' => 'cancelled',
        ]);

        $leave = $leave->fresh(['staffMember.department', 'staffMember.user', 'manager', 'manager.user']);

        $this->notifyManager(
            $leave,
            'Leave request revoked',
            sprintf(
                '%s revoked their %s request for %s to %s.',
                $this->staffName($leave->staffMember),
                $this->leaveTypeLabel($leave->leave_type),
                $leave->start_date?->format('Y-m-d'),
                $leave->end_date?->format('Y-m-d')
            ),
            'revoked'
        );

        return $leave;
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
            'submitted_at' => $leave->submitted_at?->toDateTimeString(),
            'manager_approved_at' => $leave->manager_approved_at?->toDateTimeString(),
            'hr_approved_at' => $leave->hr_approved_at?->toDateTimeString(),
            'created_at' => $leave->created_at?->toDateTimeString(),
            'updated_at' => $leave->updated_at?->toDateTimeString(),
            'can_revoke' => in_array($leave->status, ['submitted', 'manager_approved'], true),
            'balance_impact' => [
                'bucket' => $leave->leave_type,
                'days' => (float) $leave->total_days,
            ],
        ];
    }

    public function mapLeaveDetail(LeaveRequest $leave, ?User $user = null): array
    {
        $staff = $user?->staffMember;
        $isHrUser = $this->isHrUser($user);
        $canManagerAction = $staff && (int) $leave->manager_id === (int) $staff->id && $leave->status === 'submitted';
        $canHrAction = $isHrUser && $leave->status === 'manager_approved';
        $canRevoke = $staff && (int) $leave->staff_member_id === (int) $staff->id && in_array($leave->status, ['submitted', 'manager_approved'], true);

        return array_merge($this->mapLeave($leave), [
            'staff_member' => [
                'id' => $leave->staffMember?->id,
                'name' => $this->staffName($leave->staffMember),
                'email' => $leave->staffMember?->email,
                'employee_number' => $leave->staffMember?->employee_number,
                'department_name' => $leave->staffMember?->department?->name,
            ],
            'manager' => [
                'id' => $leave->manager?->id,
                'name' => $this->staffName($leave->manager),
                'email' => $leave->manager?->email,
            ],
            'status_label' => $this->statusLabel($leave->status),
            'requested_period' => [
                'start_date' => $leave->start_date?->format('Y-m-d'),
                'end_date' => $leave->end_date?->format('Y-m-d'),
                'total_days' => (float) $leave->total_days,
                'calculated_working_days' => (float) $leave->total_days,
            ],
            'timeline' => $this->timelineForLeave($leave),
            'permissions' => [
                'can_manager_approve' => (bool) $canManagerAction,
                'can_manager_reject' => (bool) $canManagerAction,
                'can_hr_approve' => (bool) $canHrAction,
                'can_hr_reject' => (bool) $canHrAction,
                'can_revoke' => (bool) $canRevoke,
                'is_hr_user' => $isHrUser,
                'is_manager_user' => $staff ? (int) $leave->manager_id === (int) $staff->id : false,
                'is_requester' => $staff ? (int) $leave->staff_member_id === (int) $staff->id : false,
            ],
        ]);
    }

    public function canViewLeaveRequest(User $user, LeaveRequest $leave): bool
    {
        if ($this->isHrUser($user)) {
            return true;
        }

        $staff = $user->staffMember;
        if (! $staff) {
            return false;
        }

        if ((int) $leave->staff_member_id === (int) $staff->id) {
            return true;
        }

        if ((int) $leave->manager_id === (int) $staff->id) {
            return true;
        }

        return $leave->status === 'hr_approved'
            && $staff->department_id !== null
            && (int) $leave->staffMember?->department_id === (int) $staff->department_id;
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

    protected function assertNoOverlappingLeaveRequest(StaffMember $staff, Carbon $start, Carbon $end): void
    {
        $hasOverlap = LeaveRequest::query()
            ->where('staff_member_id', $staff->id)
            ->whereIn('status', ['submitted', 'manager_approved', 'hr_approved'])
            ->whereDate('start_date', '<=', $end->format('Y-m-d'))
            ->whereDate('end_date', '>=', $start->format('Y-m-d'))
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_date' => 'A leave request already exists for part or all of the selected period.',
            ]);
        }
    }

    protected function timelineForLeave(LeaveRequest $leave): array
    {
        $timeline = [
            [
                'key' => 'submitted',
                'label' => 'Request submitted',
                'timestamp' => $leave->submitted_at?->toDateTimeString() ?? $leave->created_at?->toDateTimeString(),
                'actor' => $this->staffName($leave->staffMember),
                'comment' => $leave->reason,
                'status' => 'completed',
            ],
        ];

        if ($leave->manager_comment || $leave->manager_approved_at || in_array($leave->status, ['manager_rejected'], true)) {
            $timeline[] = [
                'key' => 'manager_review',
                'label' => in_array($leave->status, ['manager_rejected'], true) ? 'Manager rejected request' : 'Manager reviewed request',
                'timestamp' => $leave->manager_approved_at?->toDateTimeString() ?? $leave->updated_at?->toDateTimeString(),
                'actor' => $this->staffName($leave->manager),
                'comment' => $leave->manager_comment,
                'status' => in_array($leave->status, ['manager_rejected'], true) ? 'rejected' : 'completed',
            ];
        }

        if ($leave->hr_comment || $leave->hr_approved_at || in_array($leave->status, ['hr_rejected'], true)) {
            $timeline[] = [
                'key' => 'hr_review',
                'label' => in_array($leave->status, ['hr_rejected'], true) ? 'HR rejected request' : 'HR reviewed request',
                'timestamp' => $leave->hr_approved_at?->toDateTimeString() ?? $leave->updated_at?->toDateTimeString(),
                'actor' => 'Human Resources',
                'comment' => $leave->hr_comment,
                'status' => in_array($leave->status, ['hr_rejected'], true) ? 'rejected' : 'completed',
            ];
        }

        if ($leave->status === 'cancelled') {
            $timeline[] = [
                'key' => 'revoked',
                'label' => 'Request revoked',
                'timestamp' => $leave->updated_at?->toDateTimeString(),
                'actor' => $this->staffName($leave->staffMember),
                'comment' => null,
                'status' => 'cancelled',
            ];
        }

        return $timeline;
    }

    protected function isHrUser(?User $user): bool
    {
        return (bool) $user && (
            $user->can('domain.human-resources.view')
            || $user->can('domain.human-resources.manage')
        );
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'submitted' => 'Awaiting Manager Approval',
            'manager_approved' => 'Awaiting HR Approval',
            'manager_rejected' => 'Rejected by Manager',
            'hr_approved' => 'Approved',
            'hr_rejected' => 'Rejected by HR',
            'cancelled' => 'Revoked',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    }

    protected function notifyRequester(LeaveRequest $leave, string $title, string $message, string $event): void
    {
        $requester = $leave->staffMember?->user;

        if ($requester) {
            $requester->notify(new LeaveRequestNotification($leave, $title, $message, $event));
        }
    }

    protected function notifyManager(LeaveRequest $leave, string $title, string $message, string $event): void
    {
        $manager = $leave->manager?->user;

        if ($manager) {
            $manager->notify(new LeaveRequestNotification($leave, $title, $message, $event));
        }
    }

    protected function notifyHrReviewers(LeaveRequest $leave, string $title, string $message, string $event): void
    {
        $this->hrReviewers()
            ->each(fn (User $user) => $user->notify(new LeaveRequestNotification($leave, $title, $message, $event)));
    }

    protected function hrReviewers(): Collection
    {
        return User::query()
            ->permission('domain.human-resources.manage')
            ->get()
            ->unique('id')
            ->values();
    }

    protected function staffName(?StaffMember $staff): string
    {
        if (! $staff) {
            return 'Staff member';
        }

        return trim($staff->first_name.' '.$staff->last_name);
    }

    protected function leaveTypeLabel(?string $leaveType): string
    {
        return $leaveType === 'sick' ? 'Sick Leave' : 'Annual Leave';
    }
}
