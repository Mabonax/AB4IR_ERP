<?php

namespace App\Domains\TaskManagement\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\TaskManagement\Notifications\TaskActivityNotification;
use App\Domains\TaskManagement\Models\WorkTaskDocument;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Models\WorkTaskHistory;
use App\Domains\TaskManagement\Notifications\TaskAssignedNotification;
use App\Domains\TaskManagement\Notifications\TaskOverdueReminderNotification;
use App\Domains\TaskManagement\Repositories\WorkTaskRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkTaskService
{
    public function __construct(
        protected WorkTaskRepositoryInterface $repository,
        protected TaskWorkflowGovernance $governance,
    ) {}

    public function paginateForUser(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkTask::query()
            ->with([
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
            ])
            ->withCount(['marketingRequests', 'marketingDeliverables'])
            ->latest();

        $query = $this->applyFilters($this->visibleQuery($query, $actor), $filters);

        return $this->repository->paginateVisible($query, $perPage);
    }

    public function createTask(array $data, User $actor): WorkTask
    {
        return DB::transaction(function () use ($data, $actor) {
            $creatorDepartmentId = (int) ($actor->staffMember?->department_id ?? 0) ?: null;
            $assignedToUserId = isset($data['assigned_to_user_id']) && $data['assigned_to_user_id'] !== ''
                ? (int) $data['assigned_to_user_id']
                : null;
            $assignedDepartmentId = isset($data['assigned_department_id']) && $data['assigned_department_id'] !== ''
                ? (int) $data['assigned_department_id']
                : null;
            $projectId = isset($data['project_id']) && $data['project_id'] !== '' ? (int) $data['project_id'] : null;
            $programId = isset($data['program_id']) && $data['program_id'] !== '' ? (int) $data['program_id'] : null;

            if (! $assignedToUserId && ! $assignedDepartmentId) {
                throw ValidationException::withMessages([
                    'assigned_to_user_id' => ['Select an assignee or target department.'],
                ]);
            }

            $this->assertAssignmentAllowed($actor, $assignedToUserId, $assignedDepartmentId, $projectId);

            $task = $this->repository->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => 'open',
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
                'context_type' => $projectId ? 'project' : ($programId ? 'program' : 'general'),
                'project_id' => $projectId,
                'program_id' => $programId,
                'creator_user_id' => $actor->id,
                'creator_department_id' => $creatorDepartmentId,
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_department_id' => $assignedDepartmentId,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $this->recordHistory($task, $actor, 'created', sprintf(
                'Task created and assigned to %s.',
                $task->assignee?->name
                    ?? $task->assignedDepartment?->name
                    ?? 'the target queue'
            ));

            $this->notifyAssignmentRecipients($task, 'A new task has been assigned to your work queue.');

            return $this->loadTaskRelations($task);
        });
    }

    public function updateStatus(WorkTask $task, array $data, ?User $actor = null): WorkTask
    {
        $status = $data['status'];

        return DB::transaction(function () use ($task, $data, $status, $actor) {
            $this->assertStatusTransitionAllowed($task, $status, $actor);

            $originalStatus = $task->status;
            $task = $this->repository->update($task, [
                'status' => $status,
                'completion_notes' => $data['completion_notes'] ?? null,
                'completed_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            if ($originalStatus !== $status) {
                $statusSummary = sprintf(
                    'Status changed from %s to %s.',
                    str_replace('_', ' ', $originalStatus),
                    str_replace('_', ' ', $status)
                );

                $this->recordHistory(
                    $task,
                    $actor,
                    'status_updated',
                    $statusSummary
                );

                $this->notifyUsers(
                    $this->interactionRecipients($task),
                    new TaskActivityNotification(
                        $task,
                        'Task status updated',
                        sprintf('%s updated task "%s". %s', $actor?->name ?? 'A user', $task->title, $statusSummary)
                    )
                );
            }

            return $this->loadTaskRelations($task);
        });
    }

    public function submitForReview(WorkTask $task, array $data, User $actor): WorkTask
    {
        return DB::transaction(function () use ($task, $data, $actor) {
            if ((int) ($task->assigned_to_user_id ?? 0) !== (int) $actor->id) {
                throw ValidationException::withMessages([
                    'status' => ['Only the assigned user can submit this task for manager review.'],
                ]);
            }

            if (! in_array($task->status, ['open', 'in_progress', 'blocked', 'changes_requested'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only active or returned tasks can be submitted for review.'],
                ]);
            }

            $this->syncTaskProof($task, $data, $data['proof_file'] ?? null);

            $task = $this->repository->update($task, [
                'status' => 'pending_review',
                'completion_notes' => $data['completion_notes'] ?? $task->completion_notes,
                'submitted_for_review_at' => now(),
                'submitted_by_user_id' => $actor->id,
                'manager_review_notes' => null,
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
                'returned_for_amendments_at' => null,
                'completed_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $summary = sprintf('%s submitted the completed work for manager review.', $actor->name);
            $this->recordHistory($task, $actor, 'submitted_for_review', $summary, [
                'proof_url' => $task->proof_url,
                'proof_file_name' => $task->proof_file_name,
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    'Task submitted for review',
                    sprintf('%s submitted task "%s" for manager review.', $actor->name, $task->title)
                )
            );

            return $this->loadTaskRelations($task);
        });
    }

    public function approveCompletion(WorkTask $task, array $data, User $actor): WorkTask
    {
        return $this->completeTaskTransaction(
            $task,
            $data,
            $actor,
            'approved_completion',
            'approved the submitted work and completed the task',
            'Task approved and completed',
            'approved task "%s" and marked it complete.'
        );
    }

    public function finalizeCompletion(WorkTask $task, array $data, User $actor): WorkTask
    {
        return $this->completeTaskTransaction(
            $task,
            $data,
            $actor,
            'finalized_completion',
            'approved the submitted work, finalized the task, and closed the transaction',
            'Task finalized and closed',
            'approved, finalized, and closed task "%s".'
        );
    }

    protected function completeTaskTransaction(
        WorkTask $task,
        array $data,
        User $actor,
        string $historyAction,
        string $historyVerb,
        string $notificationTitle,
        string $notificationTemplate,
    ): WorkTask
    {
        return DB::transaction(function () use ($task, $data, $actor, $historyAction, $historyVerb, $notificationTitle, $notificationTemplate) {
            $this->assertReviewableTask($task);

            $task = $this->repository->update($task, [
                'status' => 'completed',
                'manager_review_notes' => $data['manager_review_notes'] ?? null,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $actor->id,
                'returned_for_amendments_at' => null,
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by_user_id' => $actor->id,
            ]);

            $summary = sprintf('%s %s.', $actor->name, $historyVerb);
            $this->recordHistory($task, $actor, $historyAction, $summary, [
                'manager_review_notes' => $data['manager_review_notes'] ?? null,
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    $notificationTitle,
                    sprintf('%s '.$notificationTemplate, $actor->name, $task->title)
                )
            );

            return $this->loadTaskRelations($task);
        });
    }

    public function returnForAmendments(WorkTask $task, array $data, User $actor): WorkTask
    {
        return DB::transaction(function () use ($task, $data, $actor) {
            $this->assertReviewableTask($task);

            $task = $this->repository->update($task, [
                'status' => 'changes_requested',
                'manager_review_notes' => $data['manager_review_notes'] ?? null,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $actor->id,
                'returned_for_amendments_at' => now(),
                'completed_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $summary = sprintf('%s returned the task for further amendments.', $actor->name);
            $this->recordHistory($task, $actor, 'returned_for_amendments', $summary, [
                'manager_review_notes' => $data['manager_review_notes'] ?? null,
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    'Task returned for amendments',
                    sprintf('%s returned task "%s" for further amendments.', $actor->name, $task->title)
                )
            );

            return $this->loadTaskRelations($task);
        });
    }

    public function addComment(WorkTask $task, User $actor, string $message): WorkTask
    {
        return DB::transaction(function () use ($task, $actor, $message) {
            $task->comments()->create([
                'user_id' => $actor->id,
                'message' => $message,
            ]);

            $this->recordHistory($task, $actor, 'comment_added', 'Task comment added.');

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    'New task comment',
                    sprintf('%s commented on task "%s".', $actor->name, $task->title)
                )
            );

            return $this->loadTaskRelations($task->fresh());
        });
    }

    public function reassignTask(WorkTask $task, array $data, User $actor): WorkTask
    {
        return DB::transaction(function () use ($task, $data, $actor) {
            $assignedToUserId = isset($data['assigned_to_user_id']) && $data['assigned_to_user_id'] !== ''
                ? (int) $data['assigned_to_user_id']
                : null;
            $assignedDepartmentId = isset($data['assigned_department_id']) && $data['assigned_department_id'] !== ''
                ? (int) $data['assigned_department_id']
                : null;

            if (! $assignedToUserId && ! $assignedDepartmentId) {
                throw ValidationException::withMessages([
                    'assigned_to_user_id' => ['Select an assignee or target department.'],
                ]);
            }

            $this->assertAssignmentAllowed($actor, $assignedToUserId, $assignedDepartmentId, $task->project_id ? (int) $task->project_id : null);

            $originalAssignee = $task->assignee?->name ?? $task->assignedDepartment?->name ?? 'the current queue';
            $previousRecipients = $this->assignmentRecipients($task);
            $previousAssignee = $task->assignee;
            $previousUserId = $task->assigned_to_user_id ? (int) $task->assigned_to_user_id : null;
            $previousDepartmentId = $task->assigned_department_id ? (int) $task->assigned_department_id : null;
            $status = $this->resolveReassignmentStatus($task, $data);

            $task = $this->repository->update($task, [
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_department_id' => $assignedDepartmentId,
                'status' => $status,
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
                'completed_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $task->loadMissing(['assignee:id,name', 'assignedDepartment:id,name']);
            $newAssignee = $task->assignee?->name ?? $task->assignedDepartment?->name ?? 'the target queue';
            $reason = trim((string) ($data['reason'] ?? ''));

            $summary = sprintf('Task reassigned from %s to %s.', $originalAssignee, $newAssignee);
            if ($reason !== '') {
                $summary .= ' Reason: '.$reason;
            }
            $summary .= ' Workflow status reset to '.str_replace('_', ' ', $status).'.';

            $this->recordHistory($task, $actor, 'reassigned', $summary, [
                'previous_assigned_to_user_id' => $previousUserId,
                'previous_assigned_department_id' => $previousDepartmentId,
                'new_assigned_to_user_id' => $assignedToUserId,
                'new_assigned_department_id' => $assignedDepartmentId,
                'status' => $status,
                'reason' => $reason !== '' ? $reason : null,
            ]);

            $this->notifyTaskReassignment($task, $actor, $summary, $previousRecipients, $previousAssignee);

            return $this->loadTaskRelations($task->fresh());
        });
    }

    public function uploadDocument(WorkTask $task, array $data, User $actor): WorkTaskDocument
    {
        return DB::transaction(function () use ($task, $data, $actor) {
            /** @var UploadedFile $file */
            $file = $data['file'];

            $document = $this->recordDocumentUpload(
                $task,
                $actor,
                $file,
                $data['document_kind'],
                $data['notes'] ?? null,
                title: $data['title'] ?? null,
            );

            $this->recordHistory($task, $actor, 'document_uploaded', sprintf(
                '%s uploaded %s for the task.',
                $actor->name,
                $document->title
            ), [
                'document_id' => $document->id,
                'document_kind' => $document->document_kind,
                'file_name' => $document->file_name,
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    'Task document uploaded',
                    sprintf('%s uploaded "%s" to task "%s".', $actor->name, $document->title, $task->title)
                )
            );

            return $document->load('uploader:id,name');
        });
    }

    public function updateDocument(WorkTaskDocument $document, array $data, User $actor): WorkTaskDocument
    {
        return DB::transaction(function () use ($document, $data, $actor) {
            $task = $document->task()->firstOrFail();
            $updates = [
                'title' => filled($data['title'] ?? null) ? trim((string) $data['title']) : $document->title,
                'document_kind' => $data['document_kind'],
                'notes' => $data['notes'] ?? null,
            ];

            if (($data['file'] ?? null) instanceof UploadedFile) {
                /** @var UploadedFile $file */
                $file = $data['file'];

                if ($document->path && $document->disk) {
                    Storage::disk($document->disk)->delete($document->path);
                }

                $path = $file->store("work-task-documents/{$task->id}", 'local');

                $updates = array_merge($updates, [
                    'disk' => 'local',
                    'path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            $document->forceFill($updates)->save();

            $this->recordHistory($task, $actor, 'document_updated', sprintf(
                '%s updated supporting evidence "%s".',
                $actor->name,
                $document->title
            ), [
                'document_id' => $document->id,
                'document_kind' => $document->document_kind,
                'file_name' => $document->file_name,
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    'Supporting evidence updated',
                    sprintf('%s updated supporting evidence "%s" on task "%s".', $actor->name, $document->title, $task->title)
                )
            );

            return $document->fresh(['uploader:id,name']);
        });
    }

    public function deleteDocument(WorkTaskDocument $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor): void {
            $task = $document->task()->firstOrFail();
            $meta = [
                'document_id' => $document->id,
                'document_kind' => $document->document_kind,
                'file_name' => $document->file_name,
            ];
            $title = $document->title;

            if ($document->path && $document->disk) {
                Storage::disk($document->disk)->delete($document->path);
            }

            $document->delete();

            $this->recordHistory($task, $actor, 'document_deleted', sprintf(
                '%s deleted supporting evidence "%s".',
                $actor->name,
                $title
            ), $meta);

            $this->notifyUsers(
                $this->interactionRecipients($task),
                new TaskActivityNotification(
                    $task,
                    'Supporting evidence deleted',
                    sprintf('%s deleted supporting evidence "%s" from task "%s".', $actor->name, $title, $task->title)
                )
            );
        });
    }

    public function downloadDocument(WorkTaskDocument $document)
    {
        return Storage::disk($document->disk)->download($document->path, $document->file_name);
    }

    public function previewDocument(WorkTaskDocument $document)
    {
        abort_unless($this->isPreviewableFile($document->mime_type, $document->file_name), 404);

        return Storage::disk($document->disk)->response($document->path, $document->file_name, [
            'Content-Disposition' => 'inline; filename="'.$document->file_name.'"',
        ]);
    }

    public function dashboardSummary(User $actor, array $filters = []): array
    {
        $query = $this->applyFilters(
            $this->visibleQuery(WorkTask::query(), $actor),
            $filters,
            ignore: ['status', 'overdue']
        );

        $tasks = $query->get(['status', 'due_date']);
        $today = now()->startOfDay();

        return [
            'total' => $tasks->count(),
            'open' => $tasks->where('status', 'open')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'pending_review' => $tasks->where('status', 'pending_review')->count(),
            'changes_requested' => $tasks->where('status', 'changes_requested')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $tasks->filter(fn (WorkTask $task) => ! in_array($task->status, ['completed', 'cancelled'], true) && $task->due_date && Carbon::parse($task->due_date)->lt($today))->count(),
        ];
    }

    public function operationalDashboard(User $actor): array
    {
        $visible = $this->visibleQuery(
            WorkTask::query()->with([
                'assignee:id,name',
                'assignedDepartment:id,name',
                'project:id,name',
                'program:id,title',
            ]),
            $actor
        );

        $tasks = $visible->get();
        $today = now()->startOfDay();

        $overdueTasks = $tasks
            ->filter(fn (WorkTask $task) => in_array($task->status, ['open', 'in_progress', 'blocked', 'pending_review', 'changes_requested'], true) && $task->due_date && Carbon::parse($task->due_date)->lt($today))
            ->sortBy('due_date')
            ->take(5)
            ->values();

        $unassignedQueue = $tasks
            ->filter(fn (WorkTask $task) => $task->assigned_to_user_id === null && in_array($task->status, ['open', 'in_progress', 'blocked', 'changes_requested'], true))
            ->take(5)
            ->values();

        $pendingReview = $tasks
            ->filter(fn (WorkTask $task) => $task->status === 'pending_review')
            ->sortByDesc(fn (WorkTask $task) => $task->submitted_for_review_at?->timestamp ?? 0)
            ->take(5)
            ->values();

        $workloadByAssignee = $tasks
            ->filter(fn (WorkTask $task) => $task->assigned_to_user_id !== null && in_array($task->status, ['open', 'in_progress', 'blocked', 'pending_review', 'changes_requested'], true))
            ->groupBy('assigned_to_user_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'assignee_name' => $first?->assignee?->name ?? 'Unknown',
                    'open_count' => $items->where('status', 'open')->count(),
                    'in_progress_count' => $items->where('status', 'in_progress')->count(),
                    'blocked_count' => $items->where('status', 'blocked')->count(),
                    'total_active' => $items->count(),
                ];
            })
            ->sortByDesc('total_active')
            ->take(6)
            ->values()
            ->all();

        $departmentQueues = $tasks
            ->filter(fn (WorkTask $task) => $task->assigned_department_id !== null && in_array($task->status, ['open', 'in_progress', 'blocked', 'changes_requested'], true))
            ->groupBy('assigned_department_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'department_name' => $first?->assignedDepartment?->name ?? 'Unknown',
                    'open_count' => $items->where('status', 'open')->count(),
                    'blocked_count' => $items->where('status', 'blocked')->count(),
                    'active_count' => $items->count(),
                ];
            })
            ->sortByDesc('active_count')
            ->take(6)
            ->values()
            ->all();

        return [
            'summary' => $this->dashboardSummary($actor),
            'overdue_tasks' => $overdueTasks->map(fn (WorkTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'assignee_name' => $task->assignee?->name,
                'department_name' => $task->assignedDepartment?->name,
                'context_name' => $task->project?->name ?? $task->program?->title ?? 'General',
            ])->all(),
            'unassigned_queue' => $unassignedQueue->map(fn (WorkTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'department_name' => $task->assignedDepartment?->name,
                'context_name' => $task->project?->name ?? $task->program?->title ?? 'General',
            ])->all(),
            'pending_review_tasks' => $pendingReview->map(fn (WorkTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'assignee_name' => $task->assignee?->name,
                'department_name' => $task->assignedDepartment?->name,
                'context_name' => $task->project?->name ?? $task->program?->title ?? 'General',
                'submitted_for_review_at' => $task->submitted_for_review_at?->toDateTimeString(),
            ])->all(),
            'workload_by_assignee' => $workloadByAssignee,
            'department_queues' => $departmentQueues,
        ];
    }

    public function homeDashboard(User $actor): array
    {
        $tasks = $this->visibleQuery(
            WorkTask::query()->with([
                'assignee:id,name',
                'assignedDepartment:id,name',
                'project:id,name',
                'program:id,title',
            ]),
            $actor
        )->latest()->get();

        $today = now()->startOfDay();
        $activeStatuses = ['open', 'in_progress', 'blocked', 'pending_review', 'changes_requested'];
        $departmentId = (int) ($actor->staffMember?->department_id ?? 0);

        $assigned = $tasks
            ->filter(fn (WorkTask $task) => (int) ($task->assigned_to_user_id ?? 0) === (int) $actor->id && in_array($task->status, $activeStatuses, true))
            ->sortBy(fn (WorkTask $task) => $task->due_date?->timestamp ?? PHP_INT_MAX)
            ->take(5)
            ->values();

        $created = $tasks
            ->filter(fn (WorkTask $task) => (int) $task->creator_user_id === (int) $actor->id && in_array($task->status, $activeStatuses, true))
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        $overdue = $tasks
            ->filter(fn (WorkTask $task) => in_array($task->status, $activeStatuses, true) && $task->due_date && Carbon::parse($task->due_date)->lt($today))
            ->sortBy('due_date')
            ->take(5)
            ->values();

        $queue = $tasks
            ->filter(fn (WorkTask $task) => $departmentId > 0
                && (int) ($task->assigned_department_id ?? 0) === $departmentId
                && $task->assigned_to_user_id === null
                && in_array($task->status, $activeStatuses, true))
            ->sortBy(fn (WorkTask $task) => $task->due_date?->timestamp ?? PHP_INT_MAX)
            ->take(5)
            ->values();

        $pendingReview = $tasks
            ->filter(fn (WorkTask $task) => $task->status === 'pending_review')
            ->sortByDesc(fn (WorkTask $task) => $task->submitted_for_review_at?->timestamp ?? 0)
            ->take(5)
            ->values();

        $returned = $tasks
            ->filter(fn (WorkTask $task) => $task->status === 'changes_requested')
            ->sortByDesc(fn (WorkTask $task) => $task->returned_for_amendments_at?->timestamp ?? 0)
            ->take(5)
            ->values();

        return [
            'summary' => [
                'total' => $tasks->count(),
                'assigned_to_me' => $tasks->where('assigned_to_user_id', $actor->id)->whereIn('status', $activeStatuses)->count(),
                'created_by_me' => $tasks->where('creator_user_id', $actor->id)->whereIn('status', $activeStatuses)->count(),
                'overdue' => $tasks->filter(fn (WorkTask $task) => in_array($task->status, $activeStatuses, true) && $task->due_date && Carbon::parse($task->due_date)->lt($today))->count(),
                'pending_review' => $tasks->where('status', 'pending_review')->count(),
                'changes_requested' => $tasks->where('status', 'changes_requested')->count(),
                'unassigned_queue' => $tasks->filter(fn (WorkTask $task) => $departmentId > 0
                    && (int) ($task->assigned_department_id ?? 0) === $departmentId
                    && $task->assigned_to_user_id === null
                    && in_array($task->status, $activeStatuses, true))->count(),
            ],
            'assigned' => $assigned->map(fn (WorkTask $task) => $this->mapDashboardTask($task))->all(),
            'created' => $created->map(fn (WorkTask $task) => $this->mapDashboardTask($task))->all(),
            'overdue' => $overdue->map(fn (WorkTask $task) => $this->mapDashboardTask($task))->all(),
            'queue' => $queue->map(fn (WorkTask $task) => $this->mapDashboardTask($task))->all(),
            'pending_review' => $pendingReview->map(fn (WorkTask $task) => $this->mapDashboardTask($task))->all(),
            'returned' => $returned->map(fn (WorkTask $task) => $this->mapDashboardTask($task))->all(),
        ];
    }

    public function downloadProof(WorkTask $task)
    {
        abort_if(! $task->proof_path || ! $task->proof_disk, 404);

        return Storage::disk($task->proof_disk)->download($task->proof_path, $task->proof_file_name);
    }

    public function previewProof(WorkTask $task)
    {
        abort_if(! $task->proof_path || ! $task->proof_disk, 404);
        abort_unless($this->isPreviewableFile($task->proof_mime_type, $task->proof_file_name), 404);

        return Storage::disk($task->proof_disk)->response($task->proof_path, $task->proof_file_name, [
            'Content-Disposition' => 'inline; filename="'.$task->proof_file_name.'"',
        ]);
    }

    public function sendOverdueReminders(): int
    {
        $tasks = WorkTask::query()
            ->with(['assignee:id,name', 'assignedDepartment:id,name'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNull('overdue_notified_at')
            ->get();

        foreach ($tasks as $task) {
            foreach ($this->assignmentRecipients($task) as $recipient) {
                $recipient->notify(new TaskOverdueReminderNotification($task));
            }

            $task->forceFill([
                'overdue_notified_at' => now(),
            ])->save();
        }

        return $tasks->count();
    }

    protected function visibleQuery(Builder $query, User $actor): Builder
    {
        $isTaskManager = $this->governance->isOperationalManager($actor);
        $departmentId = (int) ($actor->staffMember?->department_id ?? 0);
        $directReportUserIds = $isTaskManager && $actor->staffMember
            ? $actor->staffMember->directReports()
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];
        $managedProjectIds = $actor->staffMember
            ? Project::query()
                ->where('project_manager_id', $actor->staffMember->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        return $query->where(function (Builder $builder) use ($actor, $departmentId, $directReportUserIds, $managedProjectIds) {
            $builder->where('creator_user_id', $actor->id)
                ->orWhere('assigned_to_user_id', $actor->id);

            if ($departmentId > 0) {
                $builder->orWhere('assigned_department_id', $departmentId);
            }

            if ($directReportUserIds !== []) {
                $builder->orWhereIn('assigned_to_user_id', $directReportUserIds);
            }

            if ($managedProjectIds !== []) {
                $builder->orWhereIn('project_id', $managedProjectIds);
            }
        });
    }

    protected function mapDashboardTask(WorkTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'assignee_name' => $task->assignee?->name,
            'department_name' => $task->assignedDepartment?->name,
            'context_name' => $task->project?->name ?? $task->program?->title ?? 'General',
        ];
    }

    protected function applyFilters(Builder $query, array $filters, array $ignore = []): Builder
    {
        if (! in_array('status', $ignore, true) && filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['department_id'] ?? null)) {
            $query->where('assigned_department_id', (int) $filters['department_id']);
        }

        if (filled($filters['project_id'] ?? null)) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (filled($filters['program_id'] ?? null)) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        if (filled($filters['assignee_user_id'] ?? null)) {
            $query->where('assigned_to_user_id', (int) $filters['assignee_user_id']);
        }

        if (! in_array('overdue', $ignore, true) && filter_var($filters['overdue'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereDate('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled']);
        }

        if (filled($filters['marketing_operations'] ?? null)) {
            $filter = (string) $filters['marketing_operations'];

            if ($filter === 'linked') {
                $query->where(fn (Builder $builder) => $builder
                    ->has('marketingRequests')
                    ->orHas('marketingDeliverables'));
            }

            if ($filter === 'unlinked') {
                $query->where(fn (Builder $builder) => $builder
                    ->doesntHave('marketingRequests')
                    ->doesntHave('marketingDeliverables'));
            }
        }

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    protected function assertAssignmentAllowed(User $actor, ?int $assignedToUserId, ?int $assignedDepartmentId, ?int $projectId): void
    {
        if ($this->governance->isSuperUser($actor)) {
            return;
        }

        $staff = $actor->staffMember;
        if (! $this->governance->canCreateDepartmentTask($actor)) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => ['Only managers can create and assign tasks.'],
            ]);
        }

        $isProjectManager = $projectId ? $this->governance->managesProject($actor, $projectId) : false;

        if ($assignedToUserId && (int) $assignedToUserId === (int) $actor->id) {
            return;
        }

        if (! $assignedToUserId && $assignedDepartmentId && (int) $assignedDepartmentId === (int) $staff->department_id) {
            return;
        }

        if ($assignedToUserId) {
            $assignee = User::query()->with('staffMember')->findOrFail($assignedToUserId);
            $assigneeStaff = $assignee->staffMember;

            if ($isProjectManager) {
                return;
            }

            if ($assigneeStaff && (int) $assigneeStaff->manager_id === (int) $staff?->id) {
                return;
            }
        }

        if ($assignedDepartmentId && $isProjectManager) {
            StaffDepartment::query()->findOrFail($assignedDepartmentId);

            return;
        }

        throw ValidationException::withMessages([
            'assigned_to_user_id' => ['You may only assign general tasks to yourself, your department, or your direct reports. Cross-department assignment requires a project you manage.'],
        ]);
    }

    protected function recordHistory(WorkTask $task, ?User $actor, string $action, string $summary, array $meta = []): WorkTaskHistory
    {
        return $task->history()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'summary' => $summary,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    protected function notifyAssignmentRecipients(WorkTask $task, string $context, ?User $exclude = null): void
    {
        foreach ($this->interactionRecipients($task, $exclude) as $recipient) {
            $recipient->notify(new TaskAssignedNotification($task, $context));
        }

        $task->forceFill([
            'assignment_notified_at' => now(),
            'overdue_notified_at' => null,
        ])->save();
    }

    protected function assignmentRecipients(WorkTask $task, ?User $exclude = null): Collection
    {
        $recipients = collect();

        if ($task->assigned_to_user_id) {
            $user = User::query()->find($task->assigned_to_user_id);
            if ($user) {
                $recipients->push($user);
            }
        }

        if ($task->assigned_department_id) {
            $departmentUsers = $this->departmentRoutingRecipients((int) $task->assigned_department_id)
                ->get();
            $recipients = $recipients->concat($departmentUsers);
        }

        return $recipients
            ->filter(fn (User $user) => $exclude === null || (int) $user->id !== (int) $exclude->id)
            ->unique('id')
            ->values();
    }

    protected function interactionRecipients(WorkTask $task, ?User $exclude = null): Collection
    {
        $recipients = collect([$task->creator, $task->assignee])->filter()
            ->concat($this->assignmentRecipients($task));

        return $recipients
            ->filter(fn (User $user) => $exclude === null || (int) $user->id !== (int) $exclude->id)
            ->unique('id')
            ->values();
    }

    protected function notifyUsers(Collection $users, object $notification): void
    {
        $users->unique('id')->each(fn (User $user) => $user->notify($notification));
    }

    protected function notifyTaskReassignment(WorkTask $task, User $actor, string $summary, Collection $previousRecipients, ?User $previousAssignee): void
    {
        $newRecipients = $this->assignmentRecipients($task, $actor);
        $formerRecipients = $previousRecipients
            ->filter(fn (User $user) => (int) $user->id !== (int) $actor->id)
            ->unique('id')
            ->values();

        $this->notifyUsers($newRecipients, new TaskAssignedNotification($task, $summary));

        if ($formerRecipients->isNotEmpty()) {
            $this->notifyUsers(
                $formerRecipients,
                new TaskActivityNotification(
                    $task,
                    'Task reassigned for further work',
                    sprintf('%s reassigned task "%s". %s', $actor->name, $task->title, $summary)
                )
            );
        }

        if ($previousAssignee && (int) ($task->assigned_to_user_id ?? 0) !== (int) $previousAssignee->id) {
            $previousAssignee->notify(new TaskActivityNotification(
                $task,
                'Task review outcome changed your assignment',
                sprintf('%s reviewed task "%s" and changed its assignment. %s', $actor->name, $task->title, $summary)
            ));
        }
    }

    protected function departmentRoutingRecipients(int $departmentId): Builder
    {
        $managerQuery = User::query()
            ->whereHas('staffMember', fn (Builder $query) => $query
                ->where('department_id', $departmentId)
                ->where(function (Builder $manager) {
                    $manager->where('is_manager', true)
                        ->orWhere('is_ceo', true);
                }));

        if ($managerQuery->exists()) {
            return $managerQuery;
        }

        return User::query()
            ->whereHas('staffMember', fn (Builder $query) => $query->where('department_id', $departmentId));
    }

    protected function loadTaskRelations(WorkTask $task): WorkTask
    {
        return $task->load([
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
        ]);
    }

    protected function assertStatusTransitionAllowed(WorkTask $task, string $status, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        if (in_array($task->status, ['pending_review', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['This task is in a managed review state and cannot be changed with the general progress update action.'],
            ]);
        }

        if (! in_array($status, ['open', 'in_progress', 'blocked', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Use the dedicated review workflow to submit, approve, or return completed work.'],
            ]);
        }

        if ($status === 'cancelled' && ! $this->governance->isOperationalManager($actor) && ! $this->governance->isSuperUser($actor)) {
            throw ValidationException::withMessages([
                'status' => ['Only managers can cancel operational tasks.'],
            ]);
        }
    }

    protected function assertReviewableTask(WorkTask $task): void
    {
        if ($task->status !== 'pending_review') {
            throw ValidationException::withMessages([
                'status' => ['Only tasks awaiting manager review can be approved or returned.'],
            ]);
        }
    }

    protected function syncTaskProof(WorkTask $task, array $data, ?UploadedFile $proofFile = null): void
    {
        if (($data['remove_proof_file'] ?? false) && $task->proof_path && $task->proof_disk) {
            Storage::disk($task->proof_disk)->delete($task->proof_path);
            $task->forceFill([
                'proof_disk' => null,
                'proof_path' => null,
                'proof_file_name' => null,
                'proof_mime_type' => null,
                'proof_file_size' => null,
            ])->save();
        }

        if ($proofFile) {
            if ($task->proof_path && $task->proof_disk) {
                Storage::disk($task->proof_disk)->delete($task->proof_path);
            }

            $path = $proofFile->store("work-task-proof/{$task->id}", 'local');

            $task->forceFill([
                'proof_disk' => 'local',
                'proof_path' => $path,
                'proof_file_name' => $proofFile->getClientOriginalName(),
                'proof_mime_type' => $proofFile->getClientMimeType(),
                'proof_file_size' => $proofFile->getSize(),
            ])->save();
        }

        $task->forceFill([
            'proof_url' => filled($data['proof_url'] ?? null) ? trim((string) $data['proof_url']) : null,
        ])->save();
    }

    protected function isPreviewableFile(?string $mimeType, ?string $fileName): bool
    {
        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        $mimeType = (string) $mimeType;

        return str_contains($mimeType, 'pdf')
            || str_starts_with($mimeType, 'image/')
            || str_starts_with($mimeType, 'text/')
            || in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'txt', 'md', 'csv'], true);
    }

    protected function isTaskManager(User $user): bool
    {
        return $this->governance->isOperationalManager($user);
    }

    protected function resolveReassignmentStatus(WorkTask $task, array $data): string
    {
        if (filled($data['status'] ?? null)) {
            $candidate = (string) $data['status'];
            if (! in_array($candidate, ['pending_review', 'completed', 'cancelled'], true)) {
                return $candidate;
            }
        }

        return in_array($task->status, ['pending_review', 'completed', 'changes_requested'], true)
            ? 'changes_requested'
            : 'open';
    }

    protected function recordDocumentUpload(
        WorkTask $task,
        User $actor,
        UploadedFile $file,
        string $documentKind,
        ?string $notes = null,
        ?string $title = null,
    ): WorkTaskDocument {
        $path = $file->store("work-task-documents/{$task->id}", 'local');

        return $task->documents()->create([
            'uploaded_by_user_id' => $actor->id,
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'document_kind' => $documentKind,
            'notes' => $notes,
            'disk' => 'local',
            'path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}
