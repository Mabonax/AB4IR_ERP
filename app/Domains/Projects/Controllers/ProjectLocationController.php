<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Requests\StoreProjectLocationRequest;
use App\Domains\Projects\Requests\UpdateProjectLocationRequest;
use App\Domains\Projects\Resources\ProjectLocationResource;
use App\Domains\Projects\Services\ProjectAccessService;
use App\Domains\Projects\Services\ProjectLocationService;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Inertia\Inertia;

class ProjectLocationController extends Controller
{
    public function __construct(
        protected ProjectLocationService $service,
        protected ProjectAccessService $access
    ) {}

    public function index()
    {
        $locations = $this->service->paginateLocations();
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $facilitators = Facilitator::select('id', 'name', 'surname')
            ->orderBy('name')
            ->get()
            ->map(fn ($facilitator) => [
                'id' => $facilitator->id,
                'name' => trim($facilitator->name.' '.$facilitator->surname),
            ]);
        $provinces = Provinces::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('ProjectLocations/Index', [
            'locations' => ProjectLocationResource::collection($locations),
            'projects' => $projects,
            'facilitators' => $facilitators,
            'provinces' => $provinces,
        ]);
    }

    public function dashboard()
    {
        $query = ProjectLocation::with([
            'project.milestones',
            'facilitator',
            'province',
            'enrollments.beneficiary',
            'milestoneAssessments',
        ])
            ->orderBy('id');

        if (! $this->access->hasFullProjectAccess()) {
            $facilitator = $this->access->currentFacilitatorOrAbort('No facilitator profile found for this account.');
            $query->where('facilitator_id', $facilitator->id);
        }

        $locations = $query->get();

        $locationRows = $locations->map(function ($location) {
            $activeMilestones = $location->project?->milestones?->where('is_active', true) ?? collect();
            $requiredMilestoneIds = $activeMilestones->where('is_required', true)->pluck('id')->all();
            $totalMilestones = $activeMilestones->count();
            $activeEnrollments = $location->enrollments->filter(fn ($enrollment) => in_array($enrollment->status, ['enrolled', 'completed'], true)
                && $enrollment->beneficiary?->attendance_status === 'active'
                && $enrollment->beneficiary?->isLifecycleActive());
            $totalBeneficiaries = $activeEnrollments->count();
            $totalPossible = $totalMilestones * $totalBeneficiaries;
            $scopedAssessments = $location->milestoneAssessments
                ->whereIn('project_milestone_id', $activeMilestones->pluck('id')->all())
                ->whereIn('beneficiary_id', $activeEnrollments->pluck('beneficiary_id')->all());
            $completedAssessments = $scopedAssessments->where('status', 'completed')->count();
            $failedAssessments = $scopedAssessments->where('status', 'failed')->count();
            $assessedAssessments = $completedAssessments + $failedAssessments;
            $requiredPossible = count($requiredMilestoneIds) * $totalBeneficiaries;
            $completedRequiredAssessments = $scopedAssessments
                ->whereIn('project_milestone_id', $requiredMilestoneIds)
                ->where('status', 'completed')
                ->count();
            $milestoneCompletionRate = $requiredPossible > 0 ? round(($completedRequiredAssessments / $requiredPossible) * 100, 2) : 0;
            $passRate = $assessedAssessments > 0 ? round(($completedAssessments / $assessedAssessments) * 100, 2) : 0;
            $status = match (true) {
                $totalBeneficiaries === 0 || $totalMilestones === 0 => 'Blocked',
                $assessedAssessments === 0 => 'Not Started',
                $requiredPossible > 0 && $completedRequiredAssessments >= $requiredPossible => 'Completed',
                $assessedAssessments < $totalPossible => 'In Progress',
                default => 'At Risk',
            };

            return [
                'id' => $location->id,
                'project_name' => $location->project?->name,
                'province' => $location->province?->name,
                'facilitator_name' => $location->facilitator
                    ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                    : null,
                'beneficiaries' => $totalBeneficiaries,
                'milestones' => $totalMilestones,
                'completed_assessments' => $completedAssessments,
                'failed_assessments' => $failedAssessments,
                'assessed_assessments' => $assessedAssessments,
                'completed_required_assessments' => $completedRequiredAssessments,
                'total_assessments' => $totalPossible,
                'outstanding_assessments' => max($totalPossible - $assessedAssessments, 0),
                'milestone_completion_rate' => $milestoneCompletionRate,
                'pass_rate' => $passRate,
                'delivery_status' => $status,
            ];
        });

        $totalLocations = $locationRows->count();
        $totalBeneficiaries = $locationRows->sum('beneficiaries');
        $completedAssessments = $locationRows->sum('completed_assessments');
        $totalAssessments = $locationRows->sum('total_assessments');
        $assessedAssessments = $locationRows->sum('assessed_assessments');

        return Inertia::render('ProjectLocations/Dashboard', [
            'stats' => [
                'total_locations' => $totalLocations,
                'total_beneficiaries' => $totalBeneficiaries,
                'completed_assessments' => $completedAssessments,
                'total_assessments' => $totalAssessments,
                'assessment_coverage_rate' => $totalAssessments > 0 ? round(($assessedAssessments / $totalAssessments) * 100, 2) : 0,
                'pass_rate' => $assessedAssessments > 0 ? round(($completedAssessments / $assessedAssessments) * 100, 2) : 0,
            ],
            'locations' => $locationRows,
        ]);
    }

    public function store(StoreProjectLocationRequest $request)
    {
        $this->service->createLocation($request->validated());

        return redirect()->back()->with('success', 'Project location created');
    }

    public function show(int $project_location)
    {
        $model = $this->service->getLocationById($project_location);

        return response()->json(new ProjectLocationResource($model));
    }

    public function update(UpdateProjectLocationRequest $request, int $project_location)
    {
        $this->service->updateLocation($project_location, $request->validated());

        return redirect()->back()->with('success', 'Project location updated');
    }

    public function destroy(int $project_location)
    {
        $this->service->deleteLocation($project_location);

        return redirect()->back()->with('success', 'Project location deleted');
    }

    public function progress(int $project_location)
    {
        $location = \App\Domains\Projects\Models\ProjectLocation::with([
            'project.program',
            'project.milestones',
            'facilitator',
            'province',
            'enrollments.beneficiary',
            'milestoneAssessments',
        ])
            ->findOrFail($project_location);

        $this->access->assertAssignedLocationAccess($location, 'You can only access progress for your assigned locations.');

        $milestones = ProjectMilestone::with('assessments')
            ->where('project_id', $location->project_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($milestone) use ($location) {
                $assessments = $milestone->assessments
                    ->where('project_location_id', $location->id);
                $assessed = $assessments->whereIn('status', ['completed', 'failed'])->count();
                $passed = $assessments->where('status', 'completed')->count();
                $failed = $assessments->where('status', 'failed')->count();

                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'description' => $milestone->description,
                    'is_required' => (bool) $milestone->is_required,
                    'pass_mark' => $milestone->pass_mark,
                    'max_score' => $milestone->max_score,
                    'expected_timing' => $milestone->expected_timing,
                    'total' => $location->enrollments->count(),
                    'assessed' => $assessed,
                    'passed' => $passed,
                    'failed' => $failed,
                    'pass_rate' => $assessed > 0 ? round(($passed / $assessed) * 100, 2) : 0,
                ];
            });

        $milestoneOptions = ProjectMilestone::where('project_id', $location->project_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'max_score' => $m->max_score,
                'pass_mark' => $m->pass_mark,
                'is_required' => (bool) $m->is_required,
                'expected_timing' => $m->expected_timing,
            ]);

        $beneficiaries = $location->enrollments->map(function ($enrollment) use ($location, $milestoneOptions) {
            $assessmentGroup = $location->milestoneAssessments
                ->where('beneficiary_id', $enrollment->beneficiary_id)
                ->keyBy('project_milestone_id');

            $activeMilestoneIds = $milestoneOptions->pluck('id');
            $activeAssessments = $assessmentGroup->only($activeMilestoneIds->all());
            $assessed = $activeAssessments->whereIn('status', ['completed', 'failed'])->count();
            $passed = $activeAssessments->where('status', 'completed')->count();
            $failed = $activeAssessments->where('status', 'failed')->count();
            $assessments = $assessmentGroup->map(function ($assessment) {
                return [
                    'status' => $assessment->status,
                    'score' => $assessment->score,
                    'comments' => $assessment->comments,
                    'assessed_at' => $assessment->assessed_at?->toDateTimeString(),
                ];
            });

            return [
                'id' => $enrollment->beneficiary_id,
                'name' => $enrollment->beneficiary
                    ? trim($enrollment->beneficiary->name.' '.$enrollment->beneficiary->surname)
                    : null,
                'assessed_milestones' => $assessed,
                'passed_milestones' => $passed,
                'failed_milestones' => $failed,
                'overall_progress' => $milestoneOptions->count() > 0 ? round(($assessed / $milestoneOptions->count()) * 100, 2) : 0,
                'status' => $this->beneficiaryProgressStatus($assessed, $passed, $failed, $milestoneOptions->count()),
                'assessments' => $assessments,
            ];
        })->filter(fn ($b) => $b['name'] !== null)->values();

        $expectedAssessments = $beneficiaries->count() * $milestoneOptions->count();
        $assessedAssessments = (int) $beneficiaries->sum('assessed_milestones');
        $passedAssessments = (int) $beneficiaries->sum('passed_milestones');
        $failedAssessments = (int) $beneficiaries->sum('failed_milestones');
        $canAssess = (bool) request()->user()?->can('store', [\App\Domains\Projects\Models\ProjectMilestoneAssessment::class, $location]);

        return Inertia::render('ProjectLocations/Progress', [
            'location' => [
                'id' => $location->id,
                'project_name' => $location->project?->name,
                'program_name' => $location->project?->program?->title,
                'project_status' => $location->project?->status,
                'province' => $location->province?->name,
                'facilitator_name' => $location->facilitator
                    ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                    : null,
            ],
            'milestones' => $milestones,
            'milestoneOptions' => $milestoneOptions,
            'beneficiaries' => $beneficiaries,
            'totalMilestones' => $milestoneOptions->count(),
            'summary' => [
                'beneficiaries_enrolled' => $beneficiaries->count(),
                'milestones_attached' => $milestoneOptions->count(),
                'assessments_completed' => $assessedAssessments,
                'passed_assessments' => $passedAssessments,
                'failed_assessments' => $failedAssessments,
                'outstanding_assessments' => max($expectedAssessments - $assessedAssessments, 0),
                'assessment_coverage_rate' => $expectedAssessments > 0 ? round(($assessedAssessments / $expectedAssessments) * 100, 2) : 0,
                'pass_rate' => $assessedAssessments > 0 ? round(($passedAssessments / $assessedAssessments) * 100, 2) : 0,
            ],
            'canAssess' => $canAssess,
            'assessmentUnavailableMessage' => $this->assessmentUnavailableMessage($location, $milestoneOptions->count(), $beneficiaries->count(), $canAssess),
        ]);
    }

    protected function beneficiaryProgressStatus(int $assessed, int $passed, int $failed, int $totalMilestones): string
    {
        if ($totalMilestones === 0 || $assessed === 0) {
            return 'Not assessed';
        }

        if ($assessed < $totalMilestones) {
            return 'In progress';
        }

        if ($failed > 0) {
            return 'Failed';
        }

        return $passed >= $totalMilestones ? 'Passed' : 'In progress';
    }

    protected function assessmentUnavailableMessage(ProjectLocation $location, int $milestones, int $beneficiaries, bool $canAssess): ?string
    {
        if ($location->project?->status === 'planned') {
            return 'Activate the project before recording milestone assessments.';
        }

        if ($location->project?->status === 'on_hold') {
            return 'Milestone assessment is unavailable while this project is paused.';
        }

        if (in_array($location->project?->status, ['completed', 'cancelled'], true)) {
            return 'This project is closed. Assessment records are read-only.';
        }

        if ($milestones === 0) {
            return 'Attach project milestones before recording performance.';
        }

        if ($beneficiaries === 0) {
            return 'No beneficiaries are enrolled at this location.';
        }

        if (! $canAssess) {
            return 'Your account can view this progress workspace, but cannot record assessments.';
        }

        return null;
    }
}
