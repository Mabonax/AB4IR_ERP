<?php

namespace App\Domains\StaffAttendance\Repositories;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceActivity;
use App\Domains\StaffAttendance\Models\StaffAttendanceOverride;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface StaffAttendanceRepositoryInterface
{
    public function findRecordForDate(int $staffId, CarbonInterface $date): ?StaffAttendanceRecord;

    public function upsertRecord(StaffMember $staff, CarbonInterface $date, array $attributes): StaffAttendanceRecord;

    public function findActiveOverrideForDate(int $staffId, CarbonInterface $date): ?StaffAttendanceOverride;

    public function findPendingRequestForDate(int $staffId, CarbonInterface $date): ?StaffAttendanceOverride;

    public function createOverride(StaffMember $staff, int $openedByUserId, CarbonInterface $date, string $reason): StaffAttendanceOverride;

    public function createLateRequest(StaffMember $staff, int $requestedByUserId, CarbonInterface $date, string $requestReason): StaffAttendanceOverride;

    public function approveLateRequest(StaffAttendanceOverride $override, int $openedByUserId, string $reason, CarbonInterface $approvedAt): StaffAttendanceOverride;

    public function markOverrideUsed(StaffAttendanceOverride $override, CarbonInterface $usedAt): StaffAttendanceOverride;

    public function createActivity(array $attributes): StaffAttendanceActivity;

    public function historyForStaff(int $staffId, int $limit = 60): Collection;

    public function openRecordsForDate(CarbonInterface $date): Collection;

    public function recentActivities(array $filters = [], int $limit = 50): Collection;

    public function recordsForRange(array $staffIds, CarbonInterface $start, CarbonInterface $end): Collection;

    public function overridesForDate(CarbonInterface $date, array $staffIds = []): Collection;
}
