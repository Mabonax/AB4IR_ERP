<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Projects\Models\AttendanceEntry;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Services\ProjectAttendanceWorkflowService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ProjectAttendanceController extends Controller
{
    public function __construct(
        protected ProjectAttendanceWorkflowService $workflow
    ) {}

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

    public function locationRegister(Request $request, int $project_location): Response
    {
        $location = $this->locationWithRelations($project_location);
        $this->authorize('viewLocation', [AttendanceRegister::class, $location]);

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
            'canManageRegister' => $request->user()?->can('manageLocation', [AttendanceRegister::class, $location]) ?? false,
            'canMarkHoliday' => $request->user()?->can('markHoliday', [AttendanceRegister::class, $location]) ?? false,
        ]);
    }

    public function saveLocationRegister(Request $request, int $project_location): RedirectResponse
    {
        $location = $this->locationWithRelations($project_location);
        $this->authorize('manageLocation', [AttendanceRegister::class, $location]);

        $facilitator = $location->facilitator;
        if (! $facilitator) {
            abort(422, 'No facilitator is assigned to this location.');
        }

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'entries' => ['required', 'array'],
            'entries.*.beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'entries.*.status' => ['required', 'in:present,absent,excused'],
            'entries.*.excused_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->workflow->saveRegister($location, $facilitator, $validated);

        return redirect()->back()->with('success', 'Attendance register saved.');
    }

    public function markHoliday(Request $request, int $project_location): RedirectResponse
    {
        $location = $this->locationWithRelations($project_location);
        $this->authorize('markHoliday', [AttendanceRegister::class, $location]);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'holiday_reason' => ['required', 'string', 'max:255'],
        ]);

        $this->workflow->markHoliday($location, $validated);

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

        $this->authorize('viewAttendanceSummary', $project);

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
        $this->authorize('export', $register);

        $location = $register->location;
        if (! $location) {
            abort(404, 'Attendance register location not found.');
        }

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
