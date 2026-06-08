<?php

namespace App\Domains\StaffAttendance\Services;

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use App\Domains\StaffAttendance\Repositories\StaffAttendanceRepositoryInterface;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StaffAttendanceService
{
    public function __construct(
        protected StaffAttendanceRepositoryInterface $repository
    ) {}

    public function timezone(): string
    {
        return (string) config('staff_attendance.timezone', 'Africa/Johannesburg');
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function normalizeTimestamp(\Carbon\CarbonInterface $timestamp): CarbonImmutable
    {
        return CarbonImmutable::parse($timestamp->format('Y-m-d H:i:s'), $this->timezone());
    }

    public function today(): CarbonImmutable
    {
        return $this->now()->startOfDay();
    }

    public function clockInCutoff(CarbonImmutable $date): CarbonImmutable
    {
        return $date->setTimeFromTimeString((string) config('staff_attendance.clock_in_cutoff', '09:00'));
    }

    public function autoClockOutAt(CarbonImmutable $date): CarbonImmutable
    {
        return $date->setTimeFromTimeString((string) config('staff_attendance.auto_clock_out_time', '17:00'));
    }

    public function clockOutPromptAt(CarbonImmutable $date): CarbonImmutable
    {
        return $this->autoClockOutAt($date)->subMinutes(5);
    }

    public function getTodayRecordForStaff(StaffMember $staff): ?StaffAttendanceRecord
    {
        return $this->repository->findRecordForDate($staff->id, $this->today());
    }

    public function selfServicePayload(User $user): array
    {
        $staff = $user->staffMember?->loadMissing(['department', 'manager']);
        abort_unless($staff, 403, 'No staff profile is linked to this account.');

        $today = $this->today();
        $record = $this->getTodayRecordForStaff($staff);
        $override = $this->repository->findActiveOverrideForDate($staff->id, $today);
        $pendingRequest = $this->repository->findPendingRequestForDate($staff->id, $today);
        $history = $this->repository->historyForStaff(
            $staff->id,
            (int) config('staff_attendance.history_limit', 60)
        );

        $clockInState = $this->canClockIn($staff, $record, $override, $pendingRequest);

        return [
            'staff' => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'department_name' => $staff->department?->name,
                'manager_name' => $staff->manager ? trim($staff->manager->first_name.' '.$staff->manager->last_name) : null,
                'status' => $staff->status,
            ],
            'today' => [
                'date' => $today->toDateString(),
                'day_label' => $today->format('l, d M Y'),
                'clock_in_cutoff' => $this->clockInCutoff($today)->format('H:i'),
                'auto_clock_out_time' => $this->autoClockOutAt($today)->format('H:i'),
                'current_time' => $this->now()->format('H:i'),
                'record' => $this->mapRecord($record),
                'active_override' => $override ? $this->mapOverride($override) : null,
                'pending_request' => $pendingRequest ? $this->mapOverride($pendingRequest) : null,
                'can_clock_in' => $clockInState['allowed'],
                'clock_in_message' => $clockInState['message'],
                'can_clock_out' => $record !== null && $record->clock_in_at !== null && $record->clock_out_at === null,
            ],
            'history' => $history->map(fn (StaffAttendanceRecord $item) => $this->mapRecord($item))->values(),
        ];
    }

    public function dashboardWidget(User $user): ?array
    {
        $staff = $user->staffMember;
        if (! $staff) {
            return null;
        }

        $today = $this->today();
        $record = $this->getTodayRecordForStaff($staff);
        $override = $this->repository->findActiveOverrideForDate($staff->id, $today);
        $pendingRequest = $this->repository->findPendingRequestForDate($staff->id, $today);
        $state = $this->canClockIn($staff, $record, $override, $pendingRequest);

        $description = $record?->clock_out_at
            ? 'Clocked in and out for today.'
            : ($record?->clock_in_at
                ? 'You are currently clocked in.'
                : $state['message']);

        return [
            'key' => 'attendance',
            'title' => 'Today attendance',
            'value' => $record?->clock_in_at ? 1 : 0,
            'description' => $description,
            'href' => '/settings/attendance',
        ];
    }

    public function sharedPromptPayload(?User $user): ?array
    {
        if (! $user?->staffMember) {
            return null;
        }

        $staff = $user->staffMember->loadMissing(['department', 'manager']);
        $today = $this->today();
        $record = $this->getTodayRecordForStaff($staff);
        $override = $this->repository->findActiveOverrideForDate($staff->id, $today);
        $pendingRequest = $this->repository->findPendingRequestForDate($staff->id, $today);
        $clockInState = $this->canClockIn($staff, $record, $override, $pendingRequest);

        return [
            'timezone' => $this->timezone(),
            'date' => $today->toDateString(),
            'clock_in_cutoff' => $this->clockInCutoff($today)->format('H:i'),
            'clock_out_prompt_at' => $this->clockOutPromptAt($today)->format('H:i'),
            'auto_clock_out_time' => $this->autoClockOutAt($today)->format('H:i'),
            'record' => $this->mapRecord($record),
            'active_override' => $override ? $this->mapOverride($override) : null,
            'pending_request' => $pendingRequest ? $this->mapOverride($pendingRequest) : null,
            'can_clock_in' => $clockInState['allowed'],
            'clock_in_message' => $clockInState['message'],
            'can_clock_out' => $record !== null && $record->clock_in_at !== null && $record->clock_out_at === null,
            'staff_name' => trim($staff->first_name.' '.$staff->last_name),
        ];
    }

    public function managementPayload(User $user, array $filters): array
    {
        $today = $this->today();
        $period = $this->normalizePeriod((string) ($filters['period'] ?? 'week'));
        $anchor = isset($filters['anchor_date']) && $filters['anchor_date'] !== ''
            ? CarbonImmutable::parse((string) $filters['anchor_date'], $this->timezone())
            : $today;
        [$start, $end] = $this->periodBounds($period, $anchor);

        $staffQuery = StaffMember::query()
            ->with(['department', 'manager'])
            ->where('status', 'active');

        if (! $user->can('domain.human-resources.view') && ! $user->can('domain.human-resources.manage') && ! $user->can('domain.staff.manage')) {
            $actorStaff = $user->staffMember;
            $staffQuery->where('manager_id', $actorStaff?->id ?? 0);
        }

        if (! empty($filters['department_id'])) {
            $staffQuery->where('department_id', (int) $filters['department_id']);
        }

        if (! empty($filters['staff_id'])) {
            $staffQuery->where('id', (int) $filters['staff_id']);
        }

        $staffMembers = $staffQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $staffIds = $staffMembers->pluck('id')->all();
        $records = $staffIds === []
            ? collect()
            : $this->repository->recordsForRange($staffIds, $start, $end);
        $overrides = $staffIds === []
            ? collect()
            : $this->repository->overridesForDate($today, $staffIds);
        $pendingRequests = $overrides->where('status', 'pending')->values();
        $approvedOverrides = $overrides->where('status', 'approved')->values();
        $activities = $this->repository->recentActivities([
            'department_id' => $filters['department_id'] ?? null,
            'staff_id' => $filters['staff_id'] ?? null,
        ]);

        return [
            'filters' => [
                'period' => $period,
                'anchor_date' => $anchor->toDateString(),
                'department_id' => isset($filters['department_id']) && $filters['department_id'] !== ''
                    ? (int) $filters['department_id']
                    : null,
                'staff_id' => isset($filters['staff_id']) && $filters['staff_id'] !== ''
                    ? (int) $filters['staff_id']
                    : null,
            ],
            'period' => [
                'label' => ucfirst($period),
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'departments' => StaffDepartment::query()
                ->orderBy('name')
                ->get()
                ->map(fn (StaffDepartment $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ])->values(),
            'staffOptions' => $staffMembers
                ->map(fn (StaffMember $staff) => [
                    'id' => $staff->id,
                    'name' => trim($staff->first_name.' '.$staff->last_name),
                    'department_name' => $staff->department?->name,
                ])->values(),
            'todayStats' => $this->todayStats($staffMembers, $today),
            'openOverrides' => $approvedOverrides->map(fn ($override) => $this->mapOverride($override))->values(),
            'pendingRequests' => $pendingRequests->map(fn ($override) => $this->mapOverride($override))->values(),
            'reportRows' => $this->buildReportRows($staffMembers, $records, $start, $end),
            'recentActivities' => $activities->map(fn ($activity) => $this->mapActivity($activity))->values(),
        ];
    }

    public function clockIn(User $user): void
    {
        $staff = $user->staffMember;
        if (! $staff) {
            throw ValidationException::withMessages([
                'attendance' => 'No staff profile is linked to this account.',
            ]);
        }

        $today = $this->today();
        $record = $this->getTodayRecordForStaff($staff);
        $override = $this->repository->findActiveOverrideForDate($staff->id, $today);
        $pendingRequest = $this->repository->findPendingRequestForDate($staff->id, $today);
        $permission = $this->canClockIn($staff, $record, $override, $pendingRequest);

        if (! $permission['allowed']) {
            throw ValidationException::withMessages([
                'attendance' => $permission['message'],
            ]);
        }

        DB::transaction(function () use ($staff, $today, $override, $user) {
            $now = $this->normalizeTimestamp($this->now());
            $clockInStatus = $override ? 'late_override' : 'on_time';

            $saved = $this->repository->upsertRecord($staff, $today, [
                'late_override_id' => $override?->id,
                'clock_in_at' => $now,
                'clock_in_status' => $clockInStatus,
                'clock_in_source' => 'self',
            ]);

            if ($override) {
                $this->repository->markOverrideUsed($override, $now);
            }

            $this->repository->createActivity([
                'staff_member_id' => $staff->id,
                'staff_attendance_record_id' => $saved->id,
                'actor_user_id' => $user->id,
                'action' => 'clock_in',
                'reason' => $override?->reason,
                'meta' => [
                    'clock_in_status' => $clockInStatus,
                    'clock_in_source' => 'self',
                ],
                'occurred_at' => $now,
            ]);
        });
    }

    public function clockOut(User $user, bool $useDefaultTime = false): void
    {
        $staff = $user->staffMember;
        if (! $staff) {
            throw ValidationException::withMessages([
                'attendance' => 'No staff profile is linked to this account.',
            ]);
        }

        $record = $this->getTodayRecordForStaff($staff);
        if (! $record || ! $record->clock_in_at) {
            throw ValidationException::withMessages([
                'attendance' => 'You must clock in before you can clock out.',
            ]);
        }

        if ($record->clock_out_at) {
            throw ValidationException::withMessages([
                'attendance' => 'Today already has a clock-out time recorded.',
            ]);
        }

        DB::transaction(function () use ($record, $user, $staff, $useDefaultTime) {
            $now = $this->normalizeTimestamp($this->now());
            $clockOutAt = $useDefaultTime
                ? $this->normalizeTimestamp($this->autoClockOutAt($this->today()))
                : $now;
            $record->forceFill([
                'clock_out_at' => $clockOutAt,
                'clock_out_source' => $useDefaultTime ? 'scheduled_prompt' : 'self',
            ])->save();

            $this->repository->createActivity([
                'staff_member_id' => $staff->id,
                'staff_attendance_record_id' => $record->id,
                'actor_user_id' => $user->id,
                'action' => 'clock_out',
                'meta' => [
                    'clock_out_source' => $useDefaultTime ? 'scheduled_prompt' : 'self',
                ],
                'occurred_at' => $clockOutAt,
            ]);
        });
    }

    public function requestLateClockIn(User $user, string $requestReason): void
    {
        $staff = $user->staffMember;
        if (! $staff) {
            throw ValidationException::withMessages([
                'attendance' => 'No staff profile is linked to this account.',
            ]);
        }

        if (! $staff->manager_id && ! $staff->is_ceo) {
            throw ValidationException::withMessages([
                'reason' => 'No line manager is assigned to this staff member.',
            ]);
        }

        $today = $this->today();
        $now = $this->normalizeTimestamp($this->now());
        $record = $this->getTodayRecordForStaff($staff);
        $activeOverride = $this->repository->findActiveOverrideForDate($staff->id, $today);
        $pendingRequest = $this->repository->findPendingRequestForDate($staff->id, $today);

        if ($record && $record->clock_in_at) {
            throw ValidationException::withMessages([
                'reason' => 'Today already has a clock-in record.',
            ]);
        }

        if ($activeOverride) {
            throw ValidationException::withMessages([
                'reason' => 'A late clock-in approval is already open for today.',
            ]);
        }

        if ($pendingRequest) {
            throw ValidationException::withMessages([
                'reason' => 'A late clock-in request is already waiting for manager approval.',
            ]);
        }

        if ($now->lessThanOrEqualTo($this->clockInCutoff($today))) {
            throw ValidationException::withMessages([
                'reason' => 'Late clock-in requests are only needed after the daily cut-off time.',
            ]);
        }

        DB::transaction(function () use ($staff, $user, $today, $requestReason, $now) {
            $request = $this->repository->createLateRequest($staff, $user->id, $today, $requestReason);

            $this->repository->createActivity([
                'staff_member_id' => $staff->id,
                'actor_user_id' => $user->id,
                'action' => 'late_request_submitted',
                'reason' => $requestReason,
                'meta' => [
                    'override_id' => $request->id,
                ],
                'occurred_at' => $now,
            ]);
        });
    }

    public function approveLateClockInRequest(User $user, StaffMember $staff, string $approvalReason): void
    {
        if ($staff->status !== 'active') {
            throw ValidationException::withMessages([
                'staff_id' => 'Late clock-in can only be opened for active staff members.',
            ]);
        }

        $today = $this->today();
        $now = $this->normalizeTimestamp($this->now());

        if ($now->lessThanOrEqualTo($this->clockInCutoff($today))) {
            throw ValidationException::withMessages([
                'reason' => 'Late clock-in overrides can only be opened after the daily cut-off time.',
            ]);
        }

        $record = $this->getTodayRecordForStaff($staff);
        if ($record && $record->clock_in_at) {
            throw ValidationException::withMessages([
                'staff_id' => 'This staff member has already clocked in for today.',
            ]);
        }

        $pendingRequest = $this->repository->findPendingRequestForDate($staff->id, $today);
        if (! $pendingRequest) {
            throw ValidationException::withMessages([
                'staff_id' => 'No pending late clock-in request exists for this staff member today.',
            ]);
        }

        DB::transaction(function () use ($staff, $user, $approvalReason, $now, $pendingRequest) {
            $override = $this->repository->approveLateRequest($pendingRequest, $user->id, $approvalReason, $now);

            $this->repository->createActivity([
                'staff_member_id' => $staff->id,
                'actor_user_id' => $user->id,
                'action' => 'late_request_approved',
                'reason' => $approvalReason,
                'meta' => [
                    'override_id' => $override->id,
                    'opened_by' => $user->email,
                ],
                'occurred_at' => $now,
            ]);
        });
    }

    public function autoClockOutOpenRecords(): int
    {
        $today = $this->today();
        $targetClockOut = $this->normalizeTimestamp($this->autoClockOutAt($today));
        $records = $this->repository->openRecordsForDate($today);

        foreach ($records as $record) {
            $record->forceFill([
                'clock_out_at' => $targetClockOut,
                'clock_out_source' => 'auto',
            ])->save();

            $this->repository->createActivity([
                'staff_member_id' => $record->staff_member_id,
                'staff_attendance_record_id' => $record->id,
                'action' => 'auto_clock_out',
                'meta' => [
                    'clock_out_source' => 'auto',
                ],
                'occurred_at' => $targetClockOut,
            ]);
        }

        return $records->count();
    }

    public function exportReportPdf(User $user, array $filters): SymfonyResponse
    {
        $payload = $this->managementPayload($user, $filters);
        $fileName = sprintf(
            'staff-attendance-%s-%s-to-%s.pdf',
            strtolower($payload['period']['label']),
            $payload['period']['start'],
            $payload['period']['end']
        );

        $pdf = Pdf::loadView('pdf.staff-attendance-report', [
            'generatedAt' => $this->now(),
            'period' => $payload['period'],
            'filters' => $payload['filters'],
            'rows' => $payload['reportRows'],
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    public function mapRecord(?StaffAttendanceRecord $record): ?array
    {
        if (! $record) {
            return null;
        }

        return [
            'id' => $record->id,
            'attendance_date' => $record->attendance_date?->format('Y-m-d'),
            'clock_in_at' => $record->clock_in_at?->setTimezone($this->timezone())->format('Y-m-d H:i'),
            'clock_out_at' => $record->clock_out_at?->setTimezone($this->timezone())->format('Y-m-d H:i'),
            'clock_in_status' => $record->clock_in_status,
            'clock_in_status_label' => $record->clock_in_status === 'late_override' ? 'Late override' : 'On time',
            'clock_in_source' => $record->clock_in_source,
            'clock_out_source' => $record->clock_out_source,
            'hours_worked' => $this->hoursWorked($record),
            'late_override_reason' => $record->lateOverride?->reason,
            'late_override_opened_by' => $record->lateOverride?->openedBy?->name,
        ];
    }

    public function mapOverride($override): array
    {
        return [
            'id' => $override->id,
            'staff_member_id' => $override->staff_member_id,
            'staff_member_name' => trim($override->staffMember?->first_name.' '.$override->staffMember?->last_name),
            'department_name' => $override->staffMember?->department?->name,
            'attendance_date' => $override->attendance_date?->format('Y-m-d'),
            'reason' => $override->reason,
            'request_reason' => $override->request_reason,
            'status' => $override->status,
            'requested_by_name' => $override->requestedBy?->name,
            'opened_by_name' => $override->openedBy?->name,
            'approved_at' => $override->approved_at?->setTimezone($this->timezone())->format('Y-m-d H:i'),
            'used_at' => $override->used_at?->setTimezone($this->timezone())->format('Y-m-d H:i'),
        ];
    }

    public function mapActivity($activity): array
    {
        return [
            'id' => $activity->id,
            'staff_member_name' => trim($activity->staffMember?->first_name.' '.$activity->staffMember?->last_name),
            'department_name' => $activity->staffMember?->department?->name,
            'action' => $activity->action,
            'action_label' => str($activity->action)->replace('_', ' ')->title()->value(),
            'reason' => $activity->reason,
            'actor_name' => $activity->actor?->name,
            'occurred_at' => $activity->occurred_at?->setTimezone($this->timezone())->format('Y-m-d H:i'),
        ];
    }

    protected function canClockIn(StaffMember $staff, ?StaffAttendanceRecord $record, $override, $pendingRequest = null): array
    {
        if ($staff->status !== 'active') {
            return ['allowed' => false, 'message' => 'Only active staff members can clock in.'];
        }

        if ($record && $record->clock_in_at) {
            return ['allowed' => false, 'message' => 'Today already has a clock-in record.'];
        }

        $today = $this->today();
        $now = $this->now();

        if ($now->greaterThanOrEqualTo($this->autoClockOutAt($today))) {
            return ['allowed' => false, 'message' => 'The daily attendance window is closed for today.'];
        }

        if ($now->lessThanOrEqualTo($this->clockInCutoff($today))) {
            return ['allowed' => true, 'message' => 'Clock-in is open.'];
        }

        if ($override) {
            return ['allowed' => true, 'message' => 'Late clock-in has been opened by your line manager.'];
        }

        if ($pendingRequest) {
            return ['allowed' => false, 'message' => 'Your late clock-in request is waiting for manager approval.'];
        }

        return ['allowed' => false, 'message' => 'Clock-in closed after 09:00. Submit a late reason so your line manager can review and approve it.'];
    }

    protected function buildReportRows(Collection $staffMembers, Collection $records, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $staffMembers->map(function (StaffMember $staff) use ($records, $start, $end) {
            $staffRecords = $records->where('staff_member_id', $staff->id)->values();
            $presentDays = $staffRecords->whereNotNull('clock_in_at')->count();
            $lateDays = $staffRecords->where('clock_in_status', 'late_override')->count();
            $autoClockOutDays = $staffRecords->where('clock_out_source', 'auto')->count();
            $totalHours = round($staffRecords->sum(fn (StaffAttendanceRecord $record) => $this->hoursWorked($record, false)), 2);

            return [
                'staff_id' => $staff->id,
                'staff_name' => trim($staff->first_name.' '.$staff->last_name),
                'department_name' => $staff->department?->name,
                'manager_name' => $staff->manager ? trim($staff->manager->first_name.' '.$staff->manager->last_name) : null,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'auto_clock_out_days' => $autoClockOutDays,
                'recorded_days' => $staffRecords->count(),
                'total_hours' => $totalHours,
                'records' => $staffRecords->map(fn (StaffAttendanceRecord $record) => $this->mapRecord($record))->values()->all(),
            ];
        })->values()->all();
    }

    protected function todayStats(Collection $staffMembers, CarbonImmutable $today): array
    {
        $records = $staffMembers->isEmpty()
            ? collect()
            : $this->repository->recordsForRange($staffMembers->pluck('id')->all(), $today, $today);

        return [
            'staff_scope' => $staffMembers->count(),
            'clocked_in' => $records->whereNotNull('clock_in_at')->count(),
            'late_overrides' => $records->where('clock_in_status', 'late_override')->count(),
            'open_sessions' => $records->filter(fn (StaffAttendanceRecord $record) => $record->clock_in_at && ! $record->clock_out_at)->count(),
            'auto_clock_outs' => $records->where('clock_out_source', 'auto')->count(),
        ];
    }

    protected function periodBounds(string $period, CarbonImmutable $anchor): array
    {
        return match ($period) {
            'month' => [$anchor->startOfMonth()->startOfDay(), $anchor->endOfMonth()->startOfDay()],
            'quarter' => [$anchor->firstOfQuarter()->startOfDay(), $anchor->lastOfQuarter()->startOfDay()],
            'year' => [$anchor->startOfYear()->startOfDay(), $anchor->endOfYear()->startOfDay()],
            default => [$anchor->startOfWeek(Carbon::MONDAY)->startOfDay(), $anchor->endOfWeek(Carbon::SUNDAY)->startOfDay()],
        };
    }

    protected function normalizePeriod(string $period): string
    {
        return in_array($period, ['week', 'month', 'quarter', 'year'], true) ? $period : 'week';
    }

    protected function hoursWorked(StaffAttendanceRecord $record, bool $format = true): float|string|null
    {
        if (! $record->clock_in_at || ! $record->clock_out_at) {
            return $format ? null : 0.0;
        }

        $minutes = $record->clock_in_at->diffInMinutes($record->clock_out_at);
        $hours = round($minutes / 60, 2);

        return $format ? number_format($hours, 2) : $hours;
    }
}
