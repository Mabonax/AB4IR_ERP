<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Requests\StoreProjectRequest;
use App\Domains\Projects\Requests\UpdateProjectRequest;
use App\Domains\Projects\Resources\ProjectResource;
use App\Domains\Projects\Services\ProjectService;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $service
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->service->paginateProjects();
        $programs = Program::select('id', 'title')->orderBy('title')->get();
        $stakeholders = Stakeholder::select('id', 'organization_name', 'name')
            ->orderBy('organization_name')
            ->get()
            ->map(fn ($stakeholder) => [
                'id' => $stakeholder->id,
                'name' => trim($stakeholder->organization_name.' - '.$stakeholder->name),
            ]);
        $staffMembers = StaffMember::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
            ]);

        return Inertia::render('Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'programs' => $programs,
            'stakeholders' => $stakeholders,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function dashboard()
    {
        $this->authorize('viewAny', Project::class);

        return Inertia::render('Projects/Dashboard', [
            'stats' => [
                'totalProjects' => Project::count(),
                'activeProjects' => Project::where('status', 'active')->count(),
                'completedProjects' => Project::where('status', 'completed')->count(),
                'totalBeneficiaries' => ProjectEnrollment::count(),
                'totalLocations' => ProjectLocation::count(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $this->authorize('create', Project::class);

        $this->service->createProject($request->validated());

        return redirect()->back()->with('success', 'Project created');
    }

    public function show(int $project)
    {
        $model = Project::with([
                'program',
                'sponsor',
                'projectManager',
                'locations.facilitator',
                'locations.province',
                'locations.enrollments.beneficiary',
                'milestones',
            ])
            ->findOrFail($project);

        $this->authorize('view', $model);

        $milestones = ProjectMilestone::with('assessments')
            ->where('project_id', $model->id)
            ->orderBy('sort_order')
            ->get();

        $locationStats = $model->locations->map(function ($location) use ($milestones) {
            $beneficiaries = $location->enrollments->map(function ($enrollment) {
                return [
                    'id' => $enrollment->beneficiary_id,
                    'name' => $enrollment->beneficiary
                        ? trim($enrollment->beneficiary->name.' '.$enrollment->beneficiary->surname)
                        : null,
                ];
            })->filter(fn ($b) => $b['name'] !== null)->values();

            $milestoneProgress = $milestones->map(function ($milestone) use ($location) {
                $total = $location->enrollments->count();
                $assessments = $milestone->assessments
                    ->where('project_location_id', $location->id);

                return [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'total' => $total,
                    'assessed' => $assessments->count(),
                    'passed' => $assessments->where('status', 'completed')->count(),
                ];
            });

            $totalBeneficiaries = $beneficiaries->count();
            $completedAll = $totalBeneficiaries > 0
                ? $milestoneProgress->every(fn ($m) => $m['assessed'] >= $totalBeneficiaries)
                    ? $totalBeneficiaries
                    : $milestoneProgress->min('assessed') ?? 0
                : 0;

            return [
                'id' => $location->id,
                'location' => $location->province?->name,
                'facilitator_name' => $location->facilitator
                    ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                    : null,
                'beneficiaries' => $beneficiaries,
                'total_beneficiaries' => $totalBeneficiaries,
                'milestones' => $milestoneProgress,
                'completed_all' => $completedAll,
            ];
        });

        return Inertia::render('Projects/Show', [
            'project' => new ProjectResource($model),
            'milestones' => $milestones,
            'locations' => $locationStats,
        ]);
    }

    public function addMilestone(Request $request, int $project)
    {
        $data = $request->validate([
            'milestone_template_id' => 'required|exists:program_milestone_templates,id',
        ]);

        $template = ProgramMilestoneTemplate::findOrFail($data['milestone_template_id']);

        $projectModel = Project::findOrFail($project);
        $this->authorize('update', $projectModel);

        if ($template->program_id !== $projectModel->program_id) {
            return redirect()->back()->withErrors([
                'milestone_template_id' => 'Template does not match project program.',
            ]);
        }

        ProjectMilestone::updateOrCreate(
            [
                'project_id' => $project,
                'program_milestone_template_id' => $template->id,
            ],
            [
                'title' => $template->title,
                'description' => $template->description,
                'sort_order' => $template->sort_order,
                'max_score' => $template->max_score,
            ]
        );

        return redirect()->back()->with('success', 'Milestone added');
    }

    public function syncMilestones(int $project)
    {
        $projectModel = Project::findOrFail($project);
        $this->authorize('update', $projectModel);
        $this->service->syncProgramMilestones($projectModel);

        return redirect()->back()->with('success', 'Program milestones synced');
    }

    public function update(UpdateProjectRequest $request, int $project)
    {
        $projectModel = Project::findOrFail($project);
        $this->authorize('update', $projectModel);

        $this->service->updateProject($project, $request->validated());

        return redirect()->back()->with('success', 'Project updated');
    }

    public function destroy(int $project)
    {
        $projectModel = Project::findOrFail($project);
        $this->authorize('delete', $projectModel);

        $this->service->deleteProject($project);

        return redirect()->back()->with('success', 'Project deleted');
    }
}
