<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Requests\StoreProjectLocationRequest;
use App\Domains\Projects\Requests\UpdateProjectLocationRequest;
use App\Domains\Projects\Resources\ProjectLocationResource;
use App\Domains\Projects\Services\ProjectLocationService;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Inertia\Inertia;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectLocation;

class ProjectLocationController extends Controller
{
    public function __construct(
        protected ProjectLocationService $service
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
        $locations = ProjectLocation::with([
                'project',
                'facilitator',
                'province',
                'enrollments',
                'milestoneAssessments',
            ])
            ->orderBy('id')
            ->get();

        $locationRows = $locations->map(function ($location) {
            $totalMilestones = ProjectMilestone::where('project_id', $location->project_id)->count();
            $totalBeneficiaries = $location->enrollments->count();
            $totalPossible = $totalMilestones * $totalBeneficiaries;
            $totalAssessments = $location->milestoneAssessments->count();
            $completedAssessments = $totalAssessments;

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
                'total_assessments' => $totalPossible,
            ];
        });

        $totalLocations = $locationRows->count();
        $totalBeneficiaries = $locationRows->sum('beneficiaries');
        $completedAssessments = $locationRows->sum('completed_assessments');
        $totalAssessments = $locationRows->sum('total_assessments');

        return Inertia::render('ProjectLocations/Dashboard', [
            'stats' => [
                'total_locations' => $totalLocations,
                'total_beneficiaries' => $totalBeneficiaries,
                'completed_assessments' => $completedAssessments,
                'total_assessments' => $totalAssessments,
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
                'project',
                'facilitator',
                'province',
                'enrollments.beneficiary',
                'milestoneAssessments',
            ])
            ->findOrFail($project_location);

        $milestones = ProjectMilestone::with('assessments')
            ->where('project_id', $location->project_id)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($milestone) use ($location) {
                $assessments = $milestone->assessments
                    ->where('project_location_id', $location->id);

                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'total' => $location->enrollments->count(),
                    'assessed' => $assessments->count(),
                    'passed' => $assessments->where('status', 'completed')->count(),
                ];
            });

        $milestoneOptions = ProjectMilestone::where('project_id', $location->project_id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'max_score' => $m->max_score,
            ]);

        $beneficiaries = $location->enrollments->map(function ($enrollment) use ($location) {
            $assessmentGroup = $location->milestoneAssessments
                ->where('beneficiary_id', $enrollment->beneficiary_id)
                ->keyBy('project_milestone_id');

            $assessed = $assessmentGroup->count();
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
                'assessments' => $assessments,
            ];
        })->filter(fn ($b) => $b['name'] !== null)->values();

        return Inertia::render('ProjectLocations/Progress', [
            'location' => [
                'id' => $location->id,
                'project_name' => $location->project?->name,
                'province' => $location->province?->name,
                'facilitator_name' => $location->facilitator
                    ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                    : null,
            ],
            'milestones' => $milestones,
            'milestoneOptions' => $milestoneOptions,
            'beneficiaries' => $beneficiaries,
            'totalMilestones' => $milestoneOptions->count(),
        ]);
    }
}
