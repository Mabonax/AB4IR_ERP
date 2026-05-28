<?php

namespace App\Domains\Marketing\Services;

use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Marketing\Models\MarketingJobDocument;
use App\Domains\Marketing\Models\MarketingJobHistory;
use App\Domains\Marketing\Notifications\MarketingJobActivityNotification;
use App\Domains\Marketing\Notifications\MarketingJobAssignedNotification;
use App\Domains\Marketing\Repositories\MarketingJobRepositoryInterface;
use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MarketingService
{
    public function __construct(
        protected MarketingJobRepositoryInterface $repository,
        protected MarketingWorkflowGovernance $governance,
    ) {}

    public function paginateForUser(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MarketingJob::query()
            ->with([
                'event:id,title',
                'creator:id,name,email',
                'assignee:id,name,email',
                'submittedBy:id,name,email',
                'reviewedBy:id,name,email',
                'closedBy:id,name,email',
                'creatorDepartment:id,name',
                'assignedDepartment:id,name',
                'documents.uploader:id,name',
                'comments.user:id,name',
                'history.actor:id,name',
            ])
            ->latest();

        $query = $this->applyFilters($this->visibleQuery($query, $actor), $filters);

        return $this->repository->paginateVisible($query, $perPage);
    }

    public function createJob(array $data, User $actor): MarketingJob
    {
        return DB::transaction(function () use ($data, $actor) {
            $creatorDepartmentId = (int) ($actor->staffMember?->department_id ?? 0) ?: null;
            $assignedToUserId = filled($data['assigned_to_user_id'] ?? null) ? (int) $data['assigned_to_user_id'] : null;
            $assignedDepartmentId = filled($data['assigned_department_id'] ?? null)
                ? (int) $data['assigned_department_id']
                : ($creatorDepartmentId ?: $this->marketingDepartmentId());

            if (! $assignedToUserId && ! $assignedDepartmentId) {
                throw ValidationException::withMessages([
                    'assigned_to_user_id' => ['Select a marketing assignee or marketing queue.'],
                ]);
            }

            $this->assertMarketingAssignmentAllowed($assignedToUserId, $assignedDepartmentId);

            $job = $this->repository->create([
                'title' => $data['title'],
                'brief' => $data['brief'] ?? null,
                'job_type' => $data['job_type'],
                'status' => 'open',
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'creator_user_id' => $actor->id,
                'creator_department_id' => $creatorDepartmentId,
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_department_id' => $assignedDepartmentId,
            ]);

            $this->recordHistory($job, $actor, 'created', sprintf(
                'Marketing work item created and routed to %s.',
                $job->assignee?->name ?? $job->assignedDepartment?->name ?? 'the marketing queue'
            ));

            $this->notifyAssignmentRecipients($job, 'A new marketing work item has been routed to your queue.', $actor);

            return $this->loadRelations($job);
        });
    }

    public function updateStatus(MarketingJob $job, array $data, User $actor): MarketingJob
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $this->assertStatusTransitionAllowed($job, $data['status'], $actor);

            $originalStatus = $job->status;
            $job = $this->repository->update($job, [
                'status' => $data['status'],
                'delivery_notes' => $data['delivery_notes'] ?? $job->delivery_notes,
                'approved_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            if ($originalStatus !== $job->status) {
                $summary = sprintf(
                    'Status changed from %s to %s.',
                    str_replace('_', ' ', $originalStatus),
                    str_replace('_', ' ', $job->status)
                );

                $this->recordHistory($job, $actor, 'status_updated', $summary);
                $this->notifyUsers(
                    $this->interactionRecipients($job, $actor),
                    new MarketingJobActivityNotification(
                        $job,
                        'Marketing work status updated',
                        sprintf('%s updated "%s". %s', $actor->name, $job->title, $summary)
                    )
                );
            }

            return $this->loadRelations($job);
        });
    }

    public function submitForApproval(MarketingJob $job, array $data, User $actor): MarketingJob
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            if ((int) ($job->assigned_to_user_id ?? 0) !== (int) $actor->id) {
                throw ValidationException::withMessages([
                    'status' => ['Only the assigned marketing staff member can submit this work for approval.'],
                ]);
            }

            if (! in_array($job->status, ['open', 'in_progress', 'blocked', 'changes_requested'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only active or returned marketing work can be submitted for approval.'],
                ]);
            }

            $this->syncProof($job, $data, $data['proof_file'] ?? null);

            $job = $this->repository->update($job, [
                'status' => 'pending_approval',
                'delivery_notes' => $data['delivery_notes'] ?? $job->delivery_notes,
                'submitted_for_approval_at' => now(),
                'submitted_by_user_id' => $actor->id,
                'approval_notes' => null,
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
                'returned_for_amendments_at' => null,
                'approved_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $summary = sprintf('%s submitted the marketing deliverable for approval.', $actor->name);
            $this->recordHistory($job, $actor, 'submitted_for_approval', $summary, [
                'proof_url' => $job->proof_url,
                'proof_file_name' => $job->proof_file_name,
            ]);

            if (($data['proof_file'] ?? null) instanceof UploadedFile) {
                $this->recordDocumentUpload(
                    $job,
                    $actor,
                    $data['proof_file'],
                    'delivery',
                    $data['delivery_notes'] ?? null,
                    title: $job->title.' delivery pack'
                );
            }

            $this->notifyUsers(
                $this->interactionRecipients($job, $actor),
                new MarketingJobActivityNotification(
                    $job,
                    'Marketing work submitted for approval',
                    sprintf('%s submitted "%s" for manager approval.', $actor->name, $job->title)
                )
            );

            return $this->loadRelations($job);
        });
    }

    public function approve(MarketingJob $job, array $data, User $actor): MarketingJob
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $this->assertReviewableJob($job);

            $job = $this->repository->update($job, [
                'status' => 'approved',
                'approval_notes' => $data['approval_notes'],
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $actor->id,
                'returned_for_amendments_at' => null,
                'approved_at' => now(),
                'closed_at' => now(),
                'closed_by_user_id' => $actor->id,
            ]);

            $this->recordHistory($job, $actor, 'approved', sprintf('%s approved and closed the marketing work item.', $actor->name), [
                'approval_notes' => $data['approval_notes'],
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($job, $actor),
                new MarketingJobActivityNotification(
                    $job,
                    'Marketing work approved',
                    sprintf('%s approved "%s" and closed the transaction.', $actor->name, $job->title)
                )
            );

            return $this->loadRelations($job);
        });
    }

    public function requestAmendments(MarketingJob $job, array $data, User $actor): MarketingJob
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $this->assertReviewableJob($job);

            $job = $this->repository->update($job, [
                'status' => 'changes_requested',
                'approval_notes' => $data['approval_notes'],
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $actor->id,
                'returned_for_amendments_at' => now(),
                'approved_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $this->recordHistory($job, $actor, 'changes_requested', sprintf('%s returned the marketing work item for amendments.', $actor->name), [
                'approval_notes' => $data['approval_notes'],
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($job, $actor),
                new MarketingJobActivityNotification(
                    $job,
                    'Marketing work returned for amendments',
                    sprintf('%s returned "%s" for further amendments.', $actor->name, $job->title)
                )
            );

            return $this->loadRelations($job);
        });
    }

    public function addComment(MarketingJob $job, User $actor, string $message): MarketingJob
    {
        return DB::transaction(function () use ($job, $actor, $message) {
            $job->comments()->create([
                'user_id' => $actor->id,
                'message' => $message,
            ]);

            $this->recordHistory($job, $actor, 'comment_added', 'Marketing workflow comment added.');

            $this->notifyUsers(
                $this->interactionRecipients($job, $actor),
                new MarketingJobActivityNotification(
                    $job,
                    'Marketing workflow comment added',
                    sprintf('%s commented on "%s".', $actor->name, $job->title)
                )
            );

            return $this->loadRelations($job->fresh());
        });
    }

    public function reassign(MarketingJob $job, array $data, User $actor): MarketingJob
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            $assignedToUserId = filled($data['assigned_to_user_id'] ?? null) ? (int) $data['assigned_to_user_id'] : null;
            $assignedDepartmentId = filled($data['assigned_department_id'] ?? null)
                ? (int) $data['assigned_department_id']
                : ($job->assigned_department_id ?: $this->marketingDepartmentId());

            if (! $assignedToUserId && ! $assignedDepartmentId) {
                throw ValidationException::withMessages([
                    'assigned_to_user_id' => ['Select a marketing assignee or marketing queue.'],
                ]);
            }

            $this->assertMarketingAssignmentAllowed($assignedToUserId, $assignedDepartmentId);

            $originalAssignee = $job->assignee?->name ?? $job->assignedDepartment?->name ?? 'the current queue';
            $previousRecipients = $this->assignmentRecipients($job);

            $job = $this->repository->update($job, [
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_department_id' => $assignedDepartmentId,
                'status' => 'changes_requested',
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
                'approved_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);

            $job->loadMissing(['assignee:id,name', 'assignedDepartment:id,name']);
            $newAssignee = $job->assignee?->name ?? $job->assignedDepartment?->name ?? 'the marketing queue';
            $summary = sprintf('Marketing work reassigned from %s to %s. Reason: %s', $originalAssignee, $newAssignee, trim((string) $data['reason']));

            $this->recordHistory($job, $actor, 'reassigned', $summary, [
                'assigned_to_user_id' => $assignedToUserId,
                'assigned_department_id' => $assignedDepartmentId,
            ]);

            $this->notifyUsers($this->assignmentRecipients($job, $actor), new MarketingJobAssignedNotification($job, $summary));
            $this->notifyUsers(
                $previousRecipients->filter(fn (User $user) => (int) $user->id !== (int) $actor->id)->values(),
                new MarketingJobActivityNotification(
                    $job,
                    'Marketing work reassigned',
                    sprintf('%s reassigned "%s". %s', $actor->name, $job->title, $summary)
                )
            );

            return $this->loadRelations($job->fresh());
        });
    }

    public function uploadDocument(MarketingJob $job, array $data, User $actor): MarketingJobDocument
    {
        return DB::transaction(function () use ($job, $data, $actor) {
            /** @var UploadedFile $file */
            $file = $data['file'];

            $document = $this->recordDocumentUpload(
                $job,
                $actor,
                $file,
                $data['document_kind'],
                $data['notes'] ?? null,
                title: $data['title'] ?? null,
            );

            $this->recordHistory($job, $actor, 'document_uploaded', sprintf('%s uploaded "%s" to the marketing transaction.', $actor->name, $document->title), [
                'document_id' => $document->id,
                'document_kind' => $document->document_kind,
            ]);

            $this->notifyUsers(
                $this->interactionRecipients($job, $actor),
                new MarketingJobActivityNotification(
                    $job,
                    'Marketing document uploaded',
                    sprintf('%s uploaded "%s" to "%s".', $actor->name, $document->title, $job->title)
                )
            );

            return $document->load('uploader:id,name');
        });
    }

    public function downloadProof(MarketingJob $job)
    {
        abort_if(! $job->proof_path || ! $job->proof_disk, 404);

        return Storage::disk($job->proof_disk)->download($job->proof_path, $job->proof_file_name);
    }

    public function downloadDocument(MarketingJobDocument $document)
    {
        return Storage::disk($document->disk)->download($document->path, $document->file_name);
    }

    public function summary(User $actor, array $filters = []): array
    {
        $jobs = $this->applyFilters($this->visibleQuery(MarketingJob::query(), $actor), $filters)->get(['status']);

        return [
            'total' => $jobs->count(),
            'open' => $jobs->where('status', 'open')->count(),
            'in_progress' => $jobs->where('status', 'in_progress')->count(),
            'pending_approval' => $jobs->where('status', 'pending_approval')->count(),
            'changes_requested' => $jobs->where('status', 'changes_requested')->count(),
            'approved' => $jobs->where('status', 'approved')->count(),
        ];
    }

    public function dashboard(User $actor): array
    {
        $jobs = $this->visibleQuery(
            MarketingJob::query()->with(['assignee:id,name', 'event:id,title']),
            $actor
        )->latest()->get();

        $activeStatuses = ['open', 'in_progress', 'blocked', 'pending_approval', 'changes_requested'];
        $isManager = $this->governance->isMarketingManager($actor);

        return [
            'persona' => $isManager ? 'manager' : 'staff',
            'can_create' => $this->governance->canCreateJob($actor),
            'summary' => [
                'total' => $jobs->count(),
                'open' => $jobs->where('status', 'open')->count(),
                'in_progress' => $jobs->where('status', 'in_progress')->count(),
                'pending_approval' => $jobs->where('status', 'pending_approval')->count(),
                'changes_requested' => $jobs->where('status', 'changes_requested')->count(),
                'approved' => $jobs->where('status', 'approved')->count(),
            ],
            'assigned' => $jobs
                ->where('assigned_to_user_id', $actor->id)
                ->whereIn('status', $activeStatuses)
                ->take(5)
                ->map(fn (MarketingJob $job) => $this->mapDashboardJob($job))
                ->values()
                ->all(),
            'pending_approval' => $jobs
                ->where('status', 'pending_approval')
                ->take(5)
                ->map(fn (MarketingJob $job) => $this->mapDashboardJob($job))
                ->values()
                ->all(),
            'changes_requested' => $jobs
                ->where('status', 'changes_requested')
                ->take(5)
                ->map(fn (MarketingJob $job) => $this->mapDashboardJob($job))
                ->values()
                ->all(),
            'by_type' => $jobs
                ->groupBy('job_type')
                ->map(fn (Collection $items, string $type) => [
                    'job_type' => $type,
                    'count' => $items->count(),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function visibleQuery(Builder $query, User $actor): Builder
    {
        $departmentId = (int) ($actor->staffMember?->department_id ?? 0);

        if ($this->governance->isMarketingManager($actor)) {

            return $query->where(function (Builder $builder) use ($actor, $departmentId) {
                $builder->where('creator_user_id', $actor->id)
                    ->orWhere('assigned_to_user_id', $actor->id);

                if ($departmentId > 0) {
                    $builder->orWhere('assigned_department_id', $departmentId)
                        ->orWhere('creator_department_id', $departmentId);
                }
            });
        }

        return $query->where(function (Builder $builder) use ($actor, $departmentId) {
            $builder->where('creator_user_id', $actor->id)
                ->orWhere('assigned_to_user_id', $actor->id);

            if ($departmentId > 0 && $this->governance->belongsToMarketingDepartment($actor)) {
                $builder->orWhere('assigned_department_id', $departmentId)
                    ->orWhere('creator_department_id', $departmentId);
            }
        });
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['job_type'] ?? null)) {
            $query->where('job_type', $filters['job_type']);
        }

        if (filled($filters['event_id'] ?? null)) {
            $query->where('event_id', (int) $filters['event_id']);
        }

        if (filled($filters['assignee_user_id'] ?? null)) {
            $query->where('assigned_to_user_id', (int) $filters['assignee_user_id']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('brief', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    protected function mapDashboardJob(MarketingJob $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'job_type' => $job->job_type,
            'status' => $job->status,
            'priority' => $job->priority,
            'due_date' => $job->due_date?->format('Y-m-d'),
            'assignee_name' => $job->assignee?->name,
            'event_name' => $job->event?->title,
        ];
    }

    protected function assertMarketingAssignmentAllowed(?int $assignedToUserId, ?int $assignedDepartmentId): void
    {
        if ($assignedDepartmentId) {
            $department = StaffDepartment::query()->findOrFail($assignedDepartmentId);
            if (strtolower(trim((string) $department->name)) !== 'marketing') {
                throw ValidationException::withMessages([
                    'assigned_department_id' => ['Marketing work can only be routed to the marketing department queue.'],
                ]);
            }
        }

        if ($assignedToUserId) {
            $assignee = User::query()->with('staffMember.department')->findOrFail($assignedToUserId);
            $departmentName = strtolower(trim((string) ($assignee->staffMember?->department?->name ?? '')));

            if ($departmentName !== 'marketing') {
                throw ValidationException::withMessages([
                    'assigned_to_user_id' => ['Marketing work can only be assigned to marketing staff.'],
                ]);
            }
        }
    }

    protected function recordHistory(MarketingJob $job, ?User $actor, string $action, string $summary, array $meta = []): MarketingJobHistory
    {
        return $job->history()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'summary' => $summary,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    protected function notifyAssignmentRecipients(MarketingJob $job, string $context, ?User $exclude = null): void
    {
        foreach ($this->assignmentRecipients($job, $exclude) as $recipient) {
            $recipient->notify(new MarketingJobAssignedNotification($job, $context));
        }

        $job->forceFill([
            'assignment_notified_at' => now(),
        ])->save();
    }

    protected function assignmentRecipients(MarketingJob $job, ?User $exclude = null): Collection
    {
        $recipients = collect();

        if ($job->assigned_to_user_id) {
            $user = User::query()->find($job->assigned_to_user_id);
            if ($user) {
                $recipients->push($user);
            }
        }

        if ($job->assigned_department_id) {
            $departmentRecipients = User::query()
                ->whereHas('staffMember', fn (Builder $query) => $query
                    ->where('department_id', $job->assigned_department_id)
                    ->where(function (Builder $manager) {
                        $manager->where('is_manager', true)
                            ->orWhere('is_ceo', true);
                    }))
                ->get();

            if ($departmentRecipients->isEmpty()) {
                $departmentRecipients = User::query()
                    ->whereHas('staffMember', fn (Builder $query) => $query->where('department_id', $job->assigned_department_id))
                    ->get();
            }

            $recipients = $recipients->concat($departmentRecipients);
        }

        return $recipients
            ->filter(fn (User $user) => $exclude === null || (int) $user->id !== (int) $exclude->id)
            ->unique('id')
            ->values();
    }

    protected function interactionRecipients(MarketingJob $job, ?User $exclude = null): Collection
    {
        $recipients = collect([$job->creator, $job->assignee])->filter()
            ->concat($this->assignmentRecipients($job));

        return $recipients
            ->filter(fn (User $user) => $exclude === null || (int) $user->id !== (int) $exclude->id)
            ->unique('id')
            ->values();
    }

    protected function notifyUsers(Collection $users, object $notification): void
    {
        $users->unique('id')->each(fn (User $user) => $user->notify($notification));
    }

    protected function assertStatusTransitionAllowed(MarketingJob $job, string $status, User $actor): void
    {
        if (in_array($job->status, ['pending_approval', 'approved', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['This marketing item is already in approval or closed state and cannot be changed here.'],
            ]);
        }

        if ($status === 'cancelled' && ! $this->governance->isMarketingManager($actor)) {
            throw ValidationException::withMessages([
                'status' => ['Only the marketing manager can cancel marketing work.'],
            ]);
        }
    }

    protected function assertReviewableJob(MarketingJob $job): void
    {
        if ($job->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'status' => ['Only marketing work awaiting approval can be approved or returned.'],
            ]);
        }
    }

    protected function syncProof(MarketingJob $job, array $data, ?UploadedFile $proofFile = null): void
    {
        if (($data['remove_proof_file'] ?? false) && $job->proof_path && $job->proof_disk) {
            Storage::disk($job->proof_disk)->delete($job->proof_path);
            $job->forceFill([
                'proof_disk' => null,
                'proof_path' => null,
                'proof_file_name' => null,
                'proof_mime_type' => null,
                'proof_file_size' => null,
            ])->save();
        }

        if ($proofFile) {
            if ($job->proof_path && $job->proof_disk) {
                Storage::disk($job->proof_disk)->delete($job->proof_path);
            }

            $path = $proofFile->store("marketing-job-proof/{$job->id}", 'local');

            $job->forceFill([
                'proof_disk' => 'local',
                'proof_path' => $path,
                'proof_file_name' => $proofFile->getClientOriginalName(),
                'proof_mime_type' => $proofFile->getClientMimeType(),
                'proof_file_size' => $proofFile->getSize(),
            ])->save();
        }

        $job->forceFill([
            'proof_url' => filled($data['proof_url'] ?? null) ? trim((string) $data['proof_url']) : null,
        ])->save();
    }

    protected function recordDocumentUpload(
        MarketingJob $job,
        User $actor,
        UploadedFile $file,
        string $documentKind,
        ?string $notes = null,
        ?string $title = null,
    ): MarketingJobDocument {
        $path = $file->store("marketing-job-documents/{$job->id}", 'local');

        return $job->documents()->create([
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

    protected function loadRelations(MarketingJob $job): MarketingJob
    {
        return $job->load([
            'event:id,title',
            'creator:id,name,email',
            'assignee:id,name,email',
            'submittedBy:id,name,email',
            'reviewedBy:id,name,email',
            'closedBy:id,name,email',
            'creatorDepartment:id,name',
            'assignedDepartment:id,name',
            'documents.uploader:id,name',
            'comments.user:id,name',
            'history.actor:id,name',
        ]);
    }

    protected function marketingDepartmentId(): ?int
    {
        return Event::query()->getQuery()->newQuery()
            ->from('staff_departments')
            ->whereRaw('LOWER(name) = ?', ['marketing'])
            ->value('id');
    }
}
