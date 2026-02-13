<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\AttendanceEntry;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProjectAttendanceController extends Controller
{
    protected function hasAdminAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->hasAnyRole(['super-admin', 'super admin', 'admin']);
    }

    protected function hasFullProjectAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->can('domain.projects.view')
            || (bool) $user?->can('domain.projects.manage');
    }

    protected function currentFacilitator(): ?Facilitator
    {
        $userId = Auth::id();
        if (! $userId) {
            return null;
        }

        $facilitator = Facilitator::query()->where('user_id', $userId)->first();
        if ($facilitator) {
            return $facilitator;
        }

        $email = Auth::user()?->email;
        if (! $email) {
            return null;
        }

        return Facilitator::query()->where('email', $email)->first();
    }

    protected function isProjectManager(Project $project): bool
    {
        $user = Auth::user();
        $staffId = $user?->staffMember?->id;

        return $staffId !== null && (int) $project->project_manager_id === (int) $staffId;
    }

    protected function locationWithRelations(int $projectLocationId): ProjectLocation
    {
        return ProjectLocation::query()
            ->with([
                'project.projectManager.user',
                'facilitator',
                'province',
                'enrollments.beneficiary',
            ])
            ->findOrFail($projectLocationId);
    }

    protected function parseAndValidateDate(Project $project, string $date): Carbon
    {
        $attendanceDate = Carbon::parse($date)->startOfDay();
        $startDate = Carbon::parse($project->start_date)->startOfDay();
        $endDate = $project->end_date
            ? Carbon::parse($project->end_date)->startOfDay()
            : Carbon::today()->startOfDay();

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

    protected function registerWithRelations(int $attendanceRegisterId): AttendanceRegister
    {
        return AttendanceRegister::query()
            ->with([
                'location.project',
                'location.province',
                'facilitator',
                'location.enrollments.beneficiary',
                'entries.beneficiary',
            ])
            ->findOrFail($attendanceRegisterId);
    }

    protected function assertCanViewLocation(ProjectLocation $location): void
    {
        if ($this->hasFullProjectAccess() || $this->hasAdminAccess()) {
            return;
        }

        $facilitator = $this->currentFacilitator();
        if (! $facilitator || (int) $location->facilitator_id !== (int) $facilitator->id) {
            abort(403, 'You can only view attendance for your assigned locations.');
        }
    }

    protected function assertCanManageRegister(ProjectLocation $location): Facilitator
    {
        if ($this->hasAdminAccess()) {
            $facilitator = $location->facilitator;
            if (! $facilitator) {
                abort(422, 'No facilitator is assigned to this location.');
            }

            return $facilitator;
        }

        $facilitator = $this->currentFacilitator();
        if (! $facilitator || (int) $location->facilitator_id !== (int) $facilitator->id) {
            abort(403, 'You can only manage attendance for your assigned locations.');
        }

        return $facilitator;
    }

    protected function assertCanMarkHoliday(Project $project): void
    {
        if (! $this->isProjectManager($project)) {
            abort(403, 'Only the project manager can mark holidays.');
        }
    }

    protected function assertCanViewProjectSummary(Project $project): void
    {
        if ($this->hasAdminAccess() || $this->isProjectManager($project)) {
            return;
        }

        abort(403, 'You are not allowed to view this project attendance summary.');
    }

    public function locationRegister(Request $request, int $project_location): Response
    {
        $location = $this->locationWithRelations($project_location);
        $this->assertCanViewLocation($location);

        $selectedDate = $request->string('date')->toString();
        if ($selectedDate === '') {
            $selectedDate = now()->format('Y-m-d');
        }

        $attendanceDate = Carbon::parse($selectedDate)->format('Y-m-d');

        $register = AttendanceRegister::query()
            ->with('entries')
            ->where('project_location_id', $location->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        $entriesByBeneficiary = $register
            ? $register->entries->keyBy('beneficiary_id')
            : collect();

        $beneficiaries = $location->enrollments
            ->map(fn ($enrollment) => $enrollment->beneficiary)
            ->filter(fn ($beneficiary) => $beneficiary && $beneficiary->attendance_status !== 'dropout')
            ->map(function ($beneficiary) use ($entriesByBeneficiary) {
                $entry = $entriesByBeneficiary->get($beneficiary->id);

                return [
                    'id' => $beneficiary->id,
                    'name' => trim(($beneficiary->name ?? '').' '.($beneficiary->surname ?? '')),
                    'status' => $entry?->status ?? 'present',
                    'excused_reason' => $entry?->excused_reason,
                    'attendance_status' => $beneficiary->attendance_status ?? 'active',
                ];
            })
            ->values();

        $dayStats = [
            'present' => $entriesByBeneficiary->where('status', 'present')->count(),
            'absent' => $entriesByBeneficiary->where('status', 'absent')->count(),
            'excused' => $entriesByBeneficiary->where('status', 'excused')->count(),
            'total' => $beneficiaries->count(),
        ];

        $history = AttendanceRegister::query()
            ->where('project_location_id', $location->id)
            ->with(['entries.beneficiary'])
            ->withCount('entries')
            ->orderByDesc('attendance_date')
            ->limit(30)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'date' => $item->attendance_date?->format('Y-m-d'),
                'day_of_week' => $item->attendance_date?->format('l'),
                'is_holiday' => (bool) $item->is_holiday,
                'holiday_reason' => $item->holiday_reason,
                'entries_count' => $item->entries_count,
                'entries' => $item->entries
                    ->map(function (AttendanceEntry $entry) {
                        $beneficiary = $entry->beneficiary;

                        return [
                            'beneficiary_id' => $entry->beneficiary_id,
                            'beneficiary_name' => $beneficiary
                                ? trim(($beneficiary->name ?? '').' '.($beneficiary->surname ?? ''))
                                : 'Unknown',
                            'status' => $entry->status,
                            'excused_reason' => $entry->excused_reason,
                        ];
                    })
                    ->sortBy('beneficiary_name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values(),
            ])
            ->values();

        return Inertia::render('ProjectLocations/Attendance', [
            'location' => [
                'id' => $location->id,
                'project_id' => $location->project_id,
                'project_name' => $location->project?->name,
                'province' => $location->province?->name,
                'training_venue_address' => $location->training_venue_address,
                'facilitator_name' => $location->facilitator
                    ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                    : null,
                'start_date' => $location->project?->start_date?->format('Y-m-d'),
                'end_date' => $location->project?->end_date?->format('Y-m-d'),
            ],
            'selectedDate' => $attendanceDate,
            'register' => $register ? [
                'id' => $register->id,
                'is_holiday' => (bool) $register->is_holiday,
                'holiday_reason' => $register->holiday_reason,
            ] : null,
            'beneficiaries' => $beneficiaries,
            'dayStats' => $dayStats,
            'history' => $history,
            'canManageRegister' => $this->hasAdminAccess()
                || ((int) ($this->currentFacilitator()?->id ?? 0) === (int) $location->facilitator_id),
            'canMarkHoliday' => $this->isProjectManager($location->project),
        ]);
    }

    public function saveLocationRegister(Request $request, int $project_location): RedirectResponse
    {
        $location = $this->locationWithRelations($project_location);
        $facilitator = $this->assertCanManageRegister($location);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'entries' => ['required', 'array'],
            'entries.*.beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'entries.*.status' => ['required', 'in:present,absent,excused'],
            'entries.*.excused_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $attendanceDate = $this->parseAndValidateDate($location->project, $validated['attendance_date']);

        $existingRegister = AttendanceRegister::query()
            ->where('project_location_id', $location->id)
            ->whereDate('attendance_date', $attendanceDate->format('Y-m-d'))
            ->first();

        if ($existingRegister?->is_holiday) {
            return redirect()->back()->withErrors([
                'attendance_date' => 'This day is marked as a holiday and cannot be edited.',
            ]);
        }

        $activeBeneficiaryIds = $location->enrollments
            ->map(fn ($enrollment) => $enrollment->beneficiary)
            ->filter(fn ($beneficiary) => $beneficiary && $beneficiary->attendance_status !== 'dropout')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

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

        return redirect()->back()->with('success', 'Attendance register saved.');
    }

    public function markHoliday(Request $request, int $project_location): RedirectResponse
    {
        $location = $this->locationWithRelations($project_location);
        $this->assertCanMarkHoliday($location->project);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'holiday_reason' => ['required', 'string', 'max:255'],
        ]);

        $attendanceDate = $this->parseAndValidateDate($location->project, $validated['attendance_date']);

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

        return redirect()->back()->with('success', 'Holiday marked successfully.');
    }

    public function projectSummary(Request $request): Response
    {
        $projects = Project::query()
            ->select('id', 'name', 'start_date', 'end_date', 'project_manager_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
            ])
            ->values();

        $selectedProjectId = (int) $request->integer('project_id');
        if (! $selectedProjectId) {
            return Inertia::render('Projects/AttendanceSummary', [
                'projects' => $projects,
                'selectedProjectId' => null,
                'summary' => null,
            ]);
        }

        $project = Project::query()
            ->with(['locations.facilitator', 'locations.province'])
            ->findOrFail($selectedProjectId);

        $this->assertCanViewProjectSummary($project);

        $locationIds = $project->locations->pluck('id');

        $registers = AttendanceRegister::query()
            ->whereIn('project_location_id', $locationIds)
            ->where('project_id', $project->id)
            ->with('entries')
            ->get();

        $statsByLocation = $project->locations->map(function ($location) use ($registers) {
            $locationRegisters = $registers->where('project_location_id', $location->id);
            $nonHolidayRegisters = $locationRegisters->where('is_holiday', false);
            $entries = $nonHolidayRegisters->flatMap(fn ($register) => $register->entries);

            $present = $entries->where('status', 'present')->count();
            $absent = $entries->where('status', 'absent')->count();
            $excused = $entries->where('status', 'excused')->count();
            $total = $entries->count();

            return [
                'location_id' => $location->id,
                'location' => $location->province?->name,
                'facilitator' => $location->facilitator
                    ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                    : null,
                'register_days' => $nonHolidayRegisters->count(),
                'holidays' => $locationRegisters->where('is_holiday', true)->count(),
                'present' => $present,
                'absent' => $absent,
                'excused' => $excused,
                'total_entries' => $total,
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        })->values();

        $overallTotal = $statsByLocation->sum('total_entries');
        $overallPresent = $statsByLocation->sum('present');
        $overallAbsent = $statsByLocation->sum('absent');
        $overallExcused = $statsByLocation->sum('excused');
        $overallHolidays = $statsByLocation->sum('holidays');
        $overallRegisterDays = $statsByLocation->sum('register_days');

        return Inertia::render('Projects/AttendanceSummary', [
            'projects' => $projects,
            'selectedProjectId' => $project->id,
            'summary' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date?->format('Y-m-d'),
                    'end_date' => $project->end_date?->format('Y-m-d'),
                ],
                'locations' => $statsByLocation,
                'overall' => [
                    'register_days' => $overallRegisterDays,
                    'holidays' => $overallHolidays,
                    'present' => $overallPresent,
                    'absent' => $overallAbsent,
                    'excused' => $overallExcused,
                    'total_entries' => $overallTotal,
                    'attendance_rate' => $overallTotal > 0 ? round(($overallPresent / $overallTotal) * 100, 2) : 0,
                ],
            ],
        ]);
    }

    public function exportRegisterPdf(int $attendance_register): SymfonyResponse
    {
        $register = $this->registerWithRelations($attendance_register);
        $location = $register->location;

        if (! $location) {
            abort(404, 'Attendance register location not found.');
        }

        $this->assertCanViewLocation($location);

        $entries = $register->entries
            ->map(function (AttendanceEntry $entry) {
                $beneficiary = $entry->beneficiary;

                return [
                    'beneficiary_name' => $beneficiary
                        ? trim(($beneficiary->name ?? '').' '.($beneficiary->surname ?? ''))
                        : 'Unknown',
                    'status' => $entry->status,
                    'excused_reason' => $entry->excused_reason,
                ];
            })
            ->sortBy('beneficiary_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $total = $entries->count();
        $present = $entries->where('status', 'present')->count();
        $absent = $entries->where('status', 'absent')->count();
        $excused = $entries->where('status', 'excused')->count();
        $attendanceRate = $total > 0 ? round((($total - $absent) / $total) * 100, 2) : 0;
        $totalStudents = $location->enrollments
            ->filter(fn ($enrollment) => $enrollment->beneficiary && $enrollment->beneficiary->attendance_status !== 'dropout')
            ->count();
        $registerReference = 'REG-'.str_pad((string) $register->id, 6, '0', STR_PAD_LEFT);

        $dateLabel = $register->attendance_date?->format('Y-m-d') ?? 'unknown-date';
        $projectName = $location->project?->name ?? 'project';
        $safeProjectName = str($projectName)->slug()->value();
        $fileName = "attendance-{$safeProjectName}-{$dateLabel}.pdf";

        $pdf = Pdf::loadView('pdf.attendance-register', [
            'register' => $register,
            'location' => $location,
            'entries' => $entries,
            'stats' => [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'excused' => $excused,
                'attendance_rate' => $attendanceRate,
            ],
            'register_reference' => $registerReference,
            'total_students' => $totalStudents,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }
}
