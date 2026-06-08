<?php

namespace App\Domains\StaffAttendance\Repositories;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceActivity;
use App\Domains\StaffAttendance\Models\StaffAttendanceOverride;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StaffAttendanceRepository implements StaffAttendanceRepositoryInterface
{
    public function findRecordForDate(int $staffId, CarbonInterface $date): ?StaffAttendanceRecord
    {
        return StaffAttendanceRecord::query()
            ->with(['lateOverride.openedBy', 'activities.actor'])
            ->where('staff_member_id', $staffId)
            ->whereDate('attendance_date', $date->toDateString())
            ->first();
    }

    public function upsertRecord(StaffMember $staff, CarbonInterface $date, array $attributes): StaffAttendanceRecord
    {
        $record = StaffAttendanceRecord::query()->firstOrNew([
            'staff_member_id' => $staff->id,
            'attendance_date' => $date->toDateString(),
        ]);

        $record->fill($attributes);
        $record->save();

        return $record->fresh(['lateOverride.openedBy', 'activities.actor']) ?? $record;
    }

    public function findActiveOverrideForDate(int $staffId, CarbonInterface $date): ?StaffAttendanceOverride
    {
        return StaffAttendanceOverride::query()
            ->with(['openedBy', 'requestedBy'])
            ->where('staff_member_id', $staffId)
            ->whereDate('attendance_date', $date->toDateString())
            ->where('status', 'approved')
            ->whereNull('used_at')
            ->latest('id')
            ->first();
    }

    public function findPendingRequestForDate(int $staffId, CarbonInterface $date): ?StaffAttendanceOverride
    {
        return StaffAttendanceOverride::query()
            ->with(['requestedBy', 'openedBy'])
            ->where('staff_member_id', $staffId)
            ->whereDate('attendance_date', $date->toDateString())
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    public function createOverride(StaffMember $staff, int $openedByUserId, CarbonInterface $date, string $reason): StaffAttendanceOverride
    {
        return StaffAttendanceOverride::query()->create([
            'staff_member_id' => $staff->id,
            'opened_by_user_id' => $openedByUserId,
            'attendance_date' => $date->toDateString(),
            'reason' => $reason,
            'status' => 'approved',
            'approved_at' => $date,
        ]);
    }

    public function createLateRequest(StaffMember $staff, int $requestedByUserId, CarbonInterface $date, string $requestReason): StaffAttendanceOverride
    {
        return StaffAttendanceOverride::query()->create([
            'staff_member_id' => $staff->id,
            'requested_by_user_id' => $requestedByUserId,
            'attendance_date' => $date->toDateString(),
            'request_reason' => $requestReason,
            'status' => 'pending',
        ]);
    }

    public function approveLateRequest(StaffAttendanceOverride $override, int $openedByUserId, string $reason, CarbonInterface $approvedAt): StaffAttendanceOverride
    {
        $override->forceFill([
            'opened_by_user_id' => $openedByUserId,
            'reason' => $reason,
            'status' => 'approved',
            'approved_at' => $approvedAt,
        ])->save();

        return $override->fresh(['requestedBy', 'openedBy']) ?? $override;
    }

    public function markOverrideUsed(StaffAttendanceOverride $override, CarbonInterface $usedAt): StaffAttendanceOverride
    {
        $override->forceFill([
            'used_at' => $usedAt,
        ])->save();

        return $override->refresh();
    }

    public function createActivity(array $attributes): StaffAttendanceActivity
    {
        return StaffAttendanceActivity::query()->create($attributes);
    }

    public function historyForStaff(int $staffId, int $limit = 60): Collection
    {
        return StaffAttendanceRecord::query()
            ->with(['lateOverride.openedBy'])
            ->where('staff_member_id', $staffId)
            ->orderByDesc('attendance_date')
            ->limit($limit)
            ->get();
    }

    public function openRecordsForDate(CarbonInterface $date): Collection
    {
        return StaffAttendanceRecord::query()
            ->with('staffMember')
            ->whereDate('attendance_date', $date->toDateString())
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->get();
    }

    public function recentActivities(array $filters = [], int $limit = 50): Collection
    {
        return StaffAttendanceActivity::query()
            ->with(['staffMember.department', 'actor'])
            ->when($filters['staff_id'] ?? null, fn ($query, $staffId) => $query->where('staff_member_id', $staffId))
            ->when($filters['department_id'] ?? null, function ($query, $departmentId) {
                $query->whereHas('staffMember', fn ($staffQuery) => $staffQuery->where('department_id', $departmentId));
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function recordsForRange(array $staffIds, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return StaffAttendanceRecord::query()
            ->with(['staffMember.department', 'lateOverride.openedBy'])
            ->whereIn('staff_member_id', $staffIds)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('attendance_date')
            ->get();
    }

    public function overridesForDate(CarbonInterface $date, array $staffIds = []): Collection
    {
        return StaffAttendanceOverride::query()
            ->with(['staffMember.department', 'openedBy', 'requestedBy'])
            ->whereDate('attendance_date', $date->toDateString())
            ->when($staffIds !== [], fn ($query) => $query->whereIn('staff_member_id', $staffIds))
            ->latest('id')
            ->get();
    }
}
