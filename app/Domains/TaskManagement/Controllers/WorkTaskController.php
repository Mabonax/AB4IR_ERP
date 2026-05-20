<?php

namespace App\Domains\TaskManagement\Controllers;

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Resources\WorkTaskResource;
use App\Domains\TaskManagement\Services\WorkTaskService;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskManagement\ReassignWorkTaskRequest;
use App\Http\Requests\TaskManagement\StoreWorkTaskRequest;
use App\Http\Requests\TaskManagement\StoreWorkTaskCommentRequest;
use App\Http\Requests\TaskManagement\UpdateWorkTaskStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkTaskController extends Controller
{
    public function __construct(
        protected WorkTaskService $service
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WorkTask::class);

        $perPage = (int) $request->integer('per_page', 15);
        $filters = $request->only([
            'status',
            'priority',
            'department_id',
            'project_id',
            'program_id',
            'assignee_user_id',
            'overdue',
            'search',
        ]);
        $tasks = $this->service->paginateForUser($request->user(), $filters, $perPage);

        return Inertia::render('TaskManagement/Tasks/Index', [
            'tasks' => WorkTaskResource::collection($tasks),
            'assignees' => User::query()
                ->whereHas('staffMember')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'filters' => $filters,
            'summary' => $this->service->dashboardSummary($request->user(), $filters),
            'can' => [
                'create' => $request->user()?->can('create', WorkTask::class) ?? false,
            ],
        ]);
    }

    public function store(StoreWorkTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkTask::class);

        $this->service->createTask($request->validated(), $request->user());

        return redirect()->route('task-management.tasks.index')
            ->with('success', 'Task created and assigned.');
    }

    public function updateStatus(UpdateWorkTaskStatusRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('updateStatus', $task);

        $this->service->updateStatus($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.index')
            ->with('success', 'Task status updated.');
    }

    public function comment(StoreWorkTaskCommentRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $this->service->addComment($task, $request->user(), $request->validated()['message']);

        return redirect()->route('task-management.tasks.index')
            ->with('success', 'Task comment added.');
    }

    public function reassign(ReassignWorkTaskRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('reassign', $task);

        $this->service->reassignTask($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.index')
            ->with('success', 'Task reassigned.');
    }
}
