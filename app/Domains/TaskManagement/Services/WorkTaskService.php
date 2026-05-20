<?php

namespace App\Domains\TaskManagement\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Models\WorkTaskHistory;
use App\Domains\TaskManagement\Notifications\TaskAssignedNotification;
use App\Domains\TaskManagement\Notifications\TaskOverdueReminderNotification;
use App\Domains\TaskManagement\Repositories\WorkTaskRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkTaskService
{
    public function __construct(
        protected WorkTaskRepositoryInterface $repository
    ) {}

    public function paginateForUser(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkTask::query()
            ->with([
                'creator:id,name,email',
                'assignee:id,name,email',
                'creatorDepartment:id,name',
                'assignedDepartment:id,name',
                'project:id,name,project_manager_id',
                'program:id,title',
                'comments.user:id,name',
                'history.actor:id,name',
            ])
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
            ]);

            $this->recordHistory($task, $actor, 'created', sprintf(
                'Task created and assigned to %s.',
                $task->assignee?->name
                    ?? $task->assignedDepartment?->name
                    ?? 'the target queue'
            ));

            $this->notifyAssignmentRecipients($task, 'A new task has been assigned to your work queue.');

            return $task->load([
                'creator:id,name,email',
                'assignee:id,name,email',
                'creatorDepartment:id,name',
                'assignedDepartment:id,name',
                'project:id,name,project_manager_id',
                'program:id,title',
                'comments.user:id,name',
                'history.actor:id,name',
            ]);
        });
    }

    public function updateStatus(WorkTask $task, array $data, ?User $actor = null): WorkTask
    {
        $status = $data['status'];

        return DB::transaction(function () use ($task, $data, $status, $actor) {
            $originalStatus = $task->status;
            $task = $this->repository->update($task, [
                'status' => $status,
                'completion_notes' => $data['completion_notes'] ?? null,
                'completed_at' => $status === 'completed' ? now() : null,
            ]);

            if ($originalStatus !== $status) {
                $this->recordHistory(
                    $task,
                    $actor,
                    'status_updated',
                    sprintf('Status changed from %s to %s.', str_replace('_', ' ', $originalStatus), str_replace('_', ' ', $status))
                );
            }

            return $task->load([
                'creator:id,name,email',
                'assignee:id,name,email',
                'creatorDepartment:id,name',
                'assignedDepartment:id,name',
                'project:id,name,project_manager_id',
                'program:id,title',
                'comments.user:id,name',
                'history.actor:id,name',
            ]);
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

            return $task->fresh([
                'creator:id,name,email',
                'assignee:id,name,email',
                'creatorDepartment:id,name',
                'assignedDepartment:id,name',
                'project:id,name,project_manager_id',
                'program:id,title',
                'comments.user:id,name',
                'history.actor:id,name',
            ]);
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

            $task = $this->repository->update($task, [
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_department_id' => $assignedDepartmentId,
                'status' => $data['status'] ?? $task->status,
            ]);

            $task->loadMissing(['assignee:id,name', 'assignedDepartment:id,name']);
            $newAssignee = $task->assignee?->name ?? $task->assignedDepartment?->name ?? 'the target queue';
            $reason = trim((string) ($data['reason'] ?? ''));

            $summary = sprintf('Task reassigned from %s to %s.', $originalAssignee, $newAssignee);
            if ($reason !== '') {
                $summary .= ' Reason: '.$reason;
            }

            $this->recordHistory($task, $actor, 'reassigned', $summary);
            $this->notifyAssignmentRecipients($task, $summary);

            return $task->fresh([
                'creator:id,name,email',
                'assignee:id,name,email',
                'creatorDepartment:id,name',
                'assignedDepartment:id,name',
                'project:id,name,project_manager_id',
                'program:id,title',
                'comments.user:id,name',
                'history.actor:id,name',
            ]);
        });
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
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $tasks->filter(fn (WorkTask $task) => $task->status !== 'completed' && $task->due_date && Carbon::parse($task->due_date)->lt($today))->count(),
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
            ->filter(fn (WorkTask $task) => in_array($task->status, ['open', 'in_progress', 'blocked'], true) && $task->due_date && Carbon::parse($task->due_date)->lt($today))
            ->sortBy('due_date')
            ->take(5)
            ->values();

        $unassignedQueue = $tasks
            ->filter(fn (WorkTask $task) => $task->assigned_to_user_id === null && in_array($task->status, ['open', 'in_progress', 'blocked'], true))
            ->take(5)
            ->values();

        $workloadByAssignee = $tasks
            ->filter(fn (WorkTask $task) => $task->assigned_to_user_id !== null && in_array($task->status, ['open', 'in_progress', 'blocked'], true))
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
            ->filter(fn (WorkTask $task) => $task->assigned_department_id !== null && in_array($task->status, ['open', 'in_progress', 'blocked'], true))
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
            'workload_by_assignee' => $workloadByAssignee,
            'department_queues' => $departmentQueues,
        ];
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
        $departmentId = (int) ($actor->staffMember?->department_id ?? 0);
        $directReportUserIds = $actor->staffMember
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
        if ($actor->hasAnyRole(['super-admin', 'super admin', 'admin'])) {
            return;
        }

        $staff = $actor->staffMember;
        if (! $staff) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => ['Only staff-linked managers can assign tasks.'],
            ]);
        }

        $isProjectManager = false;
        if ($projectId) {
            $project = Project::query()->findOrFail($projectId);
            $isProjectManager = (int) $project->project_manager_id === (int) $staff->id;
        }

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

            if ($assigneeStaff && (int) $assigneeStaff->manager_id === (int) $staff->id) {
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

    protected function notifyAssignmentRecipients(WorkTask $task, string $context): void
    {
        foreach ($this->assignmentRecipients($task) as $recipient) {
            $recipient->notify(new TaskAssignedNotification($task, $context));
        }

        $task->forceFill([
            'assignment_notified_at' => now(),
            'overdue_notified_at' => null,
        ])->save();
    }

    protected function assignmentRecipients(WorkTask $task): Collection
    {
        $recipients = collect();

        if ($task->assigned_to_user_id) {
            $user = User::query()->find($task->assigned_to_user_id);
            if ($user) {
                $recipients->push($user);
            }
        }

        if ($task->assigned_department_id) {
            $departmentUsers = User::query()
                ->whereHas('staffMember', fn (Builder $query) => $query->where('department_id', $task->assigned_department_id))
                ->get();
            $recipients = $recipients->concat($departmentUsers);
        }

        return $recipients->unique('id')->values();
    }
}
