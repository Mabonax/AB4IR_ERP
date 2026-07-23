<?php

namespace App\Domains\Projects\Services;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\AttendanceEntry;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectAttendanceWorkflowService
{
    protected const CORRECTION_WINDOW_DAYS = 1;

    public function parseAndValidateDate(Project $project, string $date): Carbon
    {
        $attendanceDate = Carbon::parse($date)->startOfDay();
        $startDate = Carbon::parse($project->start_date)->startOfDay();
        $today = Carbon::today()->startOfDay();
        $projectEndDate = $project->end_date
            ? Carbon::parse($project->end_date)->startOfDay()
            : $today;
        $endDate = $projectEndDate->lt($today) ? $projectEndDate : $today;

        if ($attendanceDate->lt($startDate) || $attendanceDate->gt($endDate)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance date must be within the project start and end dates.',
            ]);
        }

        if ($attendanceDate->isWeekend()) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance cannot be captured on weekends.',
            ]);
        }

        return $attendanceDate;
    }

    public function saveRegister(ProjectLocation $location, Facilitator $facilitator, array $validated): AttendanceRegister
    {
        return DB::transaction(function () use ($location, $facilitator, $validated) {
            $this->assertProjectAllowsAttendance($location->project);

            $attendanceDate = $this->parseAndValidateDate($location->project, $validated['attendance_date']);

            $existingRegister = AttendanceRegister::query()
                ->where('project_location_id', $location->id)
                ->whereDate('attendance_date', $attendanceDate->format('Y-m-d'))
                ->first();

            $this->assertAttendanceDateWithinCorrectionWindow($attendanceDate);

            if ($existingRegister?->is_holiday) {
                throw ValidationException::withMessages([
                    'attendance_date' => 'This day is marked as a holiday and cannot be edited.',
                ]);
            }

            $activeBeneficiaryIds = $this->activeBeneficiaryIdsForLocation($location);

            foreach ($validated['entries'] as $entry) {
                if (! in_array((int) $entry['beneficiary_id'], $activeBeneficiaryIds, true)) {
                    throw ValidationException::withMessages([
                        'entries' => 'One or more beneficiaries are not active in this location.',
                    ]);
                }

                if ($entry['status'] === 'excused' && blank($entry['excused_reason'] ?? null)) {
                    throw ValidationException::withMessages([
                        'entries' => 'Excused entries require a reason.',
                    ]);
                }
            }

            $register = AttendanceRegister::query()->updateOrCreate(
                [
                    'project_location_id' => $location->id,
                    'attendance_date' => $attendanceDate->format('Y-m-d'),
                ],
                [
                    'project_id' => $location->project_id,
                    'facilitator_id' => $facilitator->id,
                    'is_holiday' => false,
                    'holiday_reason' => null,
                    'holiday_marked_by_user_id' => null,
                ]
            );

            foreach ($validated['entries'] as $entry) {
                AttendanceEntry::query()->updateOrCreate(
                    [
                        'attendance_register_id' => $register->id,
                        'beneficiary_id' => (int) $entry['beneficiary_id'],
                    ],
                    [
                        'status' => $entry['status'],
                        'excused_reason' => $entry['status'] === 'excused'
                            ? ($entry['excused_reason'] ?? null)
                            : null,
                    ]
                );
            }

            return $register->fresh(['entries']);
        });
    }

    public function markHoliday(ProjectLocation $location, array $validated): AttendanceRegister
    {
        return DB::transaction(function () use ($location, $validated) {
            $this->assertProjectAllowsAttendance($location->project);

            $attendanceDate = $this->parseAndValidateDate($location->project, $validated['attendance_date']);
            $this->assertAttendanceDateWithinCorrectionWindow($attendanceDate);

            $register = AttendanceRegister::query()->updateOrCreate(
                [
                    'project_location_id' => $location->id,
                    'attendance_date' => $attendanceDate->format('Y-m-d'),
                ],
                [
                    'project_id' => $location->project_id,
                    'facilitator_id' => $location->facilitator_id,
                    'is_holiday' => true,
                    'holiday_reason' => $validated['holiday_reason'],
                    'holiday_marked_by_user_id' => Auth::id(),
                ]
            );

            $register->entries()->delete();

            return $register->fresh(['entries']);
        });
    }

    public function activeBeneficiaryIdsForLocation(ProjectLocation $location): array
    {
        return $location->enrollments
            ->map(fn ($enrollment) => $enrollment->beneficiary)
            ->filter(fn ($beneficiary) => $beneficiary && $beneficiary->attendance_status !== 'dropout' && $beneficiary->isLifecycleActive())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function assertProjectAllowsAttendance(Project $project): void
    {
        if ($project->status !== 'active') {
            throw ValidationException::withMessages([
                'attendance_date' => 'Attendance can only be captured or corrected while the project is active.',
            ]);
        }
    }

    protected function assertAttendanceDateWithinCorrectionWindow(Carbon $attendanceDate): void
    {
        $earliestEditableDate = Carbon::today()->subDays(self::CORRECTION_WINDOW_DAYS)->startOfDay();

        if ($attendanceDate->lt($earliestEditableDate)) {
            throw ValidationException::withMessages([
                'attendance_date' => ['Attendance can only be captured or corrected for today and the previous day.'],
            ]);
        }
    }
}
