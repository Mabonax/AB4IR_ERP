<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Programs\Models\Program;
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
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $service
    ) {}

    public function index()
    {
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
        $this->service->createProject($request->validated());

        return redirect()->back()->with('success', 'Project created');
    }

    public function show(int $project)
    {
        $model = $this->service->getProjectById($project);

        return response()->json(new ProjectResource($model));
    }

    public function update(UpdateProjectRequest $request, int $project)
    {
        $this->service->updateProject($project, $request->validated());

        return redirect()->back()->with('success', 'Project updated');
    }

    public function destroy(int $project)
    {
        $this->service->deleteProject($project);

        return redirect()->back()->with('success', 'Project deleted');
    }
}
