<?php

namespace App\Domains\TaskManagement\Controllers;

use App\Domains\Marketing\Models\MarketingRequest;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Resources\WorkTaskResource;
use App\Domains\TaskManagement\Services\WorkTaskService;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskManagement\ReassignWorkTaskRequest;
use App\Http\Requests\TaskManagement\ReviewWorkTaskCompletionRequest;
use App\Http\Requests\TaskManagement\StoreWorkTaskCommentRequest;
use App\Http\Requests\TaskManagement\StoreWorkTaskRequest;
use App\Http\Requests\TaskManagement\SubmitWorkTaskReviewRequest;
use App\Http\Requests\TaskManagement\UpdateWorkTaskDocumentRequest;
use App\Http\Requests\TaskManagement\UpdateWorkTaskStatusRequest;
use App\Http\Requests\TaskManagement\UploadWorkTaskDocumentRequest;
use App\Domains\TaskManagement\Models\WorkTaskDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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
            'marketing_operations',
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

    public function show(Request $request, WorkTask $task): Response
    {
        $this->authorize('view', $task);

        $task->load([
            'creator:id,name,email',
            'assignee:id,name,email',
            'submittedBy:id,name,email',
            'reviewedBy:id,name,email',
            'closedBy:id,name,email',
            'creatorDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name,project_manager_id',
            'program:id,title',
            'documents.uploader:id,name',
            'comments.user:id,name',
            'history.actor:id,name',
            'marketingRequests' => fn ($query) => $query->withCount('deliverables')->latest(),
            'marketingDeliverables.request:id,title,status',
        ]);

        return Inertia::render('TaskManagement/Tasks/Show', [
            'task' => WorkTaskResource::make($task)->resolve(),
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
            'canRegisterMarketingOperation' => $request->user()?->can('create', MarketingRequest::class) ?? false,
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

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task status updated.');
    }

    public function submitForReview(SubmitWorkTaskReviewRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('submitForReview', $task);

        $this->service->submitForReview($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task submitted for manager review.');
    }

    public function approveCompletion(ReviewWorkTaskCompletionRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('approveCompletion', $task);

        $this->service->approveCompletion($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task approved and completed.');
    }

    public function finalizeCompletion(ReviewWorkTaskCompletionRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('approveCompletion', $task);

        $this->service->finalizeCompletion($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task approved, finalized, and closed.');
    }

    public function returnForAmendments(ReviewWorkTaskCompletionRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('returnForAmendments', $task);

        $this->service->returnForAmendments($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task returned for amendments.');
    }

    public function comment(StoreWorkTaskCommentRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $this->service->addComment($task, $request->user(), $request->validated()['message']);

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task comment added.');
    }

    public function reassign(ReassignWorkTaskRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('reassign', $task);

        $this->service->reassignTask($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task reassigned.');
    }

    public function uploadDocument(UploadWorkTaskDocumentRequest $request, WorkTask $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $this->service->uploadDocument($task, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Task document uploaded.');
    }

    public function updateDocument(UpdateWorkTaskDocumentRequest $request, WorkTask $task, WorkTaskDocument $document): RedirectResponse
    {
        $this->authorize('comment', $task);
        abort_unless((int) $document->work_task_id === (int) $task->id, 404);

        $this->service->updateDocument($document, $request->validated(), $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Supporting evidence updated.');
    }

    public function deleteDocument(Request $request, WorkTask $task, WorkTaskDocument $document): RedirectResponse
    {
        $this->authorize('comment', $task);
        abort_unless((int) $document->work_task_id === (int) $task->id, 404);

        $this->service->deleteDocument($document, $request->user());

        return redirect()->route('task-management.tasks.show', $task)
            ->with('success', 'Supporting evidence deleted.');
    }

    public function downloadProof(Request $request, WorkTask $task): HttpResponse
    {
        $this->authorize('view', $task);

        return $this->service->downloadProof($task);
    }

    public function previewProof(Request $request, WorkTask $task): HttpResponse
    {
        $this->authorize('view', $task);

        return $this->service->previewProof($task);
    }

    public function downloadDocument(Request $request, WorkTask $task, WorkTaskDocument $document): HttpResponse
    {
        $this->authorize('view', $task);
        abort_unless((int) $document->work_task_id === (int) $task->id, 404);

        return $this->service->downloadDocument($document);
    }

    public function previewDocument(Request $request, WorkTask $task, WorkTaskDocument $document): HttpResponse
    {
        $this->authorize('view', $task);
        abort_unless((int) $document->work_task_id === (int) $task->id, 404);

        return $this->service->previewDocument($document);
    }
}
