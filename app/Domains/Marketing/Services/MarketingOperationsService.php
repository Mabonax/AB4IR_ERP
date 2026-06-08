<?php

namespace App\Domains\Marketing\Services;

use App\Domains\Marketing\Enums\MarketingDeliverableStatus;
use App\Domains\Marketing\Enums\MarketingRequestStatus;
use App\Domains\Marketing\Models\MarketingActivity;
use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Marketing\Models\MarketingDeliverable;
use App\Domains\Marketing\Models\MetricSnapshot;
use App\Domains\Marketing\Models\MarketingRequest;
use App\Domains\Marketing\Models\MarketingRequestDocument;
use App\Domains\Marketing\Models\PublicationRecord;
use App\Domains\Marketing\Repositories\MarketingRequestRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MarketingOperationsService
{
    public function __construct(
        protected MarketingRequestRepositoryInterface $repository,
        protected MarketingOperationsGovernance $governance,
    ) {}

    public function paginateRequests(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MarketingRequest::query()
            ->with([
                'requester:id,name',
                'approver:id,name',
                'project:id,name',
                'program:id,name',
                'event:id,title',
                'ownerDepartment:id,name',
                'deliverables.assignee:id,name',
            ])
            ->latest();

        $query = $this->applyRequestFilters($this->visibleRequestQuery($query, $actor), $filters);

        return $this->repository->paginateVisible($query, $perPage);
    }

    public function createRequest(array $data, User $actor): MarketingRequest
    {
        return DB::transaction(function () use ($data, $actor) {
            $request = $this->repository->create([
                'title' => $data['title'],
                'objective' => $data['objective'] ?? null,
                'description' => $data['description'] ?? null,
                'target_audience' => $data['target_audience'] ?? null,
                'campaign_goal' => $data['campaign_goal'] ?? null,
                'requester_user_id' => $actor->id,
                'approver_user_id' => $data['approver_user_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'program_id' => $data['program_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'owner_department_id' => $data['owner_department_id'] ?? ($actor->staffMember?->department_id ?: null),
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
                'status' => $data['status'] ?? MarketingRequestStatus::Submitted->value,
            ]);

            $workPackage = $request->workPackages()->create([
                'assigned_unit' => $data['work_package']['assigned_unit'],
                'operational_owner_user_id' => $data['work_package']['operational_owner_user_id'] ?? null,
                'workload_status' => $request->status,
                'planned_start_date' => $data['work_package']['planned_start_date'] ?? null,
                'planned_end_date' => $data['work_package']['planned_end_date'] ?? $request->due_date,
            ]);

            foreach ($data['deliverables'] as $deliverableData) {
                $status = filled($deliverableData['assigned_to_user_id'] ?? null)
                    ? MarketingDeliverableStatus::Assigned->value
                    : MarketingDeliverableStatus::Queued->value;

                $deliverable = $request->deliverables()->create([
                    'work_package_id' => $workPackage->id,
                    'title' => $deliverableData['title'],
                    'deliverable_type' => $deliverableData['deliverable_type'],
                    'assigned_to_user_id' => $deliverableData['assigned_to_user_id'] ?? null,
                    'assigned_unit' => $deliverableData['assigned_unit'],
                    'status' => $status,
                    'due_date' => $deliverableData['due_date'] ?? $request->due_date,
                    'review_notes' => $deliverableData['review_notes'] ?? null,
                ]);

                $this->recordActivity($request, $actor, 'deliverable_planned', sprintf(
                    'Deliverable "%s" added to the request.',
                    $deliverable->title
                ), $deliverable);
            }

            $this->recordActivity($request, $actor, 'request_created', 'Marketing request created and work package initialized.', $request, [
                'assigned_unit' => $workPackage->assigned_unit,
            ]);

            return $this->loadRequest($request);
        });
    }

    public function loadRequest(MarketingRequest $request): MarketingRequest
    {
        return $request->load([
            'requester:id,name,email',
            'approver:id,name,email',
            'project:id,name',
            'program:id,name',
            'event:id,title',
            'ownerDepartment:id,name',
            'workPackages.operationalOwner:id,name',
            'deliverables.assignee:id,name',
            'deliverables.versions.uploader:id,name',
            'deliverables.versions.approver:id,name',
            'deliverables.assets.version',
            'deliverables.assets.publications.publisher:id,name',
            'deliverables.assets.publications.metricSnapshots',
            'activities.actor:id,name',
            'comments.user:id,name',
            'documents.uploader:id,name',
        ]);
    }

    public function updateRequest(MarketingRequest $request, array $data, User $actor): MarketingRequest
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            $request->update([
                'title' => $data['title'],
                'objective' => $data['objective'] ?? null,
                'description' => $data['description'] ?? null,
                'target_audience' => $data['target_audience'] ?? null,
                'campaign_goal' => $data['campaign_goal'] ?? null,
                'approver_user_id' => $data['approver_user_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'program_id' => $data['program_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'owner_department_id' => $data['owner_department_id'] ?? null,
                'priority' => $data['priority'],
                'due_date' => $data['due_date'] ?? null,
                'status' => $data['status'],
            ]);

            $workPackage = $request->workPackages()->oldest()->first();
            if ($workPackage) {
                $workPackage->update([
                    'assigned_unit' => $data['work_package']['assigned_unit'] ?? $workPackage->assigned_unit,
                    'operational_owner_user_id' => $data['work_package']['operational_owner_user_id'] ?? null,
                    'workload_status' => $request->status,
                    'planned_start_date' => $data['work_package']['planned_start_date'] ?? null,
                    'planned_end_date' => $data['work_package']['planned_end_date'] ?? $request->due_date,
                ]);
            }

            if (filled($request->due_date)) {
                $request->deliverables()
                    ->whereNull('approved_at')
                    ->whereNull('published_at')
                    ->update(['due_date' => $request->due_date]);
            }

            $this->recordActivity($request, $actor, 'request_updated', 'Marketing request details and planning data updated.', $request, [
                'status' => $request->status,
                'due_date' => $request->due_date?->format('Y-m-d'),
            ]);

            return $this->loadRequest($request);
        });
    }

    public function workspace(User $actor): array
    {
        $deliverables = $this->visibleDeliverableQuery(MarketingDeliverable::query()->with(['request:id,title,status', 'assignee:id,name', 'versions']), $actor)
            ->latest()
            ->get();

        return [
            'summary' => [
                'queued' => $deliverables->where('status', MarketingDeliverableStatus::Queued->value)->count(),
                'in_progress' => $deliverables->where('status', MarketingDeliverableStatus::InProgress->value)->count(),
                'internal_review' => $deliverables->where('status', MarketingDeliverableStatus::InternalReview->value)->count(),
                'approved' => $deliverables->where('status', MarketingDeliverableStatus::Approved->value)->count(),
            ],
            'deliverables' => $deliverables->map(fn (MarketingDeliverable $deliverable) => [
                'id' => $deliverable->id,
                'request_id' => $deliverable->request_id,
                'title' => $deliverable->title,
                'request_title' => $deliverable->request?->title,
                'deliverable_type' => $deliverable->deliverable_type,
                'assigned_unit' => $deliverable->assigned_unit,
                'status' => $deliverable->status,
                'due_date' => $deliverable->due_date?->format('Y-m-d'),
                'assignee_name' => $deliverable->assignee?->name,
                'version_count' => $deliverable->versions->count(),
            ])->values()->all(),
        ];
    }

    public function approvals(User $actor): array
    {
        $pending = $this->visibleDeliverableQuery(MarketingDeliverable::query()->with(['request:id,title', 'assignee:id,name', 'versions']), $actor)
            ->where('status', MarketingDeliverableStatus::InternalReview->value)
            ->latest()
            ->get();

        return [
            'pending' => $pending->map(fn (MarketingDeliverable $deliverable) => [
                'id' => $deliverable->id,
                'title' => $deliverable->title,
                'request_title' => $deliverable->request?->title,
                'assigned_unit' => $deliverable->assigned_unit,
                'assignee_name' => $deliverable->assignee?->name,
                'latest_version' => $deliverable->versions->first()?->version_number,
                'review_notes' => $deliverable->review_notes,
            ])->values()->all(),
        ];
    }

    public function assetLibrary(User $actor): Collection
    {
        return MarketingAsset::query()
            ->with(['deliverable.request:id,title', 'version', 'publications.metricSnapshots', 'publications.publisher:id,name'])
            ->whereHas('deliverable.request', fn (Builder $query) => $this->visibleRequestQuery($query, $actor))
            ->latest()
            ->get();
    }

    public function publicationRegister(User $actor): Collection
    {
        return PublicationRecord::query()
            ->with(['asset.deliverable.request:id,title', 'publisher:id,name', 'metricSnapshots'])
            ->whereHas('asset.deliverable.request', fn (Builder $query) => $this->visibleRequestQuery($query, $actor))
            ->latest('published_at')
            ->get();
    }

    public function dashboard(User $actor): array
    {
        $requests = $this->visibleRequestQuery(MarketingRequest::query()->with(['deliverables.assets.publications.metricSnapshots']), $actor)
            ->latest()
            ->get();

        $deliverables = $requests->flatMap->deliverables;
        $publications = $deliverables->flatMap->assets->flatMap->publications;
        $metrics = $publications->flatMap->metricSnapshots;

        return [
            'operations' => [
                'active_requests' => $requests->whereIn('status', [
                    MarketingRequestStatus::Submitted->value,
                    MarketingRequestStatus::Planned->value,
                    MarketingRequestStatus::InProduction->value,
                    MarketingRequestStatus::InReview->value,
                    MarketingRequestStatus::PartiallyApproved->value,
                ])->count(),
                'deliverables_in_queue' => $deliverables->where('status', MarketingDeliverableStatus::Queued->value)->count(),
                'overdue_deliverables' => $deliverables->filter(fn (MarketingDeliverable $deliverable) => $deliverable->due_date && $deliverable->due_date->isPast() && ! in_array($deliverable->status, [MarketingDeliverableStatus::Approved->value, MarketingDeliverableStatus::Published->value, MarketingDeliverableStatus::Archived->value], true))->count(),
                'approvals_pending' => $deliverables->where('status', MarketingDeliverableStatus::InternalReview->value)->count(),
                'workload_by_assignee' => $deliverables->groupBy(fn (MarketingDeliverable $deliverable) => $deliverable->assignee?->name ?? 'Unassigned')->map->count()->map(fn ($count, $name) => ['label' => $name, 'count' => $count])->values()->all(),
                'workload_by_unit' => $deliverables->groupBy('assigned_unit')->map->count()->map(fn ($count, $unit) => ['label' => $unit, 'count' => $count])->values()->all(),
                'work_by_type' => $deliverables->groupBy('deliverable_type')->map->count()->map(fn ($count, $type) => ['label' => $type, 'count' => $count])->values()->all(),
                'items_published_this_week' => $publications->filter(fn (PublicationRecord $record) => $record->published_at && $record->published_at->isCurrentWeek())->count(),
            ],
            'performance' => [
                'reach' => (int) $metrics->sum('reach'),
                'impressions' => (int) $metrics->sum('impressions'),
                'engagements' => (int) $metrics->sum('engagements'),
                'clicks' => (int) $metrics->sum('clicks'),
                'conversions' => (int) $metrics->sum('conversions'),
                'followers' => (int) $metrics->sum('followers'),
                'publication_activity' => $publications->groupBy('publication_channel')->map->count()->map(fn ($count, $channel) => ['label' => $channel, 'count' => $count])->values()->all(),
                'top_campaigns' => $requests->map(function (MarketingRequest $request) {
                    $requestMetrics = $request->deliverables->flatMap->assets->flatMap->publications->flatMap->metricSnapshots;

                    return [
                        'title' => $request->title,
                        'reach' => (int) $requestMetrics->sum('reach'),
                        'engagements' => (int) $requestMetrics->sum('engagements'),
                    ];
                })->sortByDesc('reach')->take(5)->values()->all(),
                'website_referrals' => (int) $publications
                    ->where('publication_channel', 'Website')
                    ->flatMap->metricSnapshots
                    ->sum('sessions'),
            ],
            'can' => [
                'create_request' => $this->governance->canCreateRequest($actor),
                'view_performance' => $this->governance->canViewPerformanceDashboard($actor),
            ],
        ];
    }

    public function canImportMetrics(User $actor): bool
    {
        return $this->governance->canImportMetrics($actor);
    }

    public function uploadVersion(MarketingDeliverable $deliverable, array $data, User $actor): MarketingDeliverable
    {
        return DB::transaction(function () use ($deliverable, $data, $actor) {
            if (! ($data['asset_file'] ?? null) instanceof UploadedFile && ! filled($data['external_reference'] ?? null)) {
                throw ValidationException::withMessages([
                    'asset_file' => ['Upload a file or provide an external reference for the new version.'],
                ]);
            }

            $file = $data['asset_file'] ?? null;
            $path = $file instanceof UploadedFile ? $file->store("marketing-deliverables/{$deliverable->id}", 'local') : null;

            $version = $deliverable->versions()->create([
                'version_number' => ((int) $deliverable->versions()->max('version_number')) + 1,
                'uploaded_by_user_id' => $actor->id,
                'change_notes' => $data['change_notes'] ?? null,
                'asset_disk' => $path ? 'local' : null,
                'asset_path' => $path,
                'asset_file_name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
                'asset_mime_type' => $file instanceof UploadedFile ? $file->getClientMimeType() : null,
                'asset_file_size' => $file instanceof UploadedFile ? $file->getSize() : null,
                'external_reference' => $data['external_reference'] ?? null,
                'approval_status' => 'pending',
            ]);

            $deliverable->update([
                'current_version_id' => $version->id,
                'status' => MarketingDeliverableStatus::InternalReview->value,
            ]);

            $this->synchronizeRequestStatus($deliverable->request);
            $this->recordActivity($deliverable->request, $actor, 'version_uploaded', sprintf(
                'Version %d uploaded for deliverable "%s".',
                $version->version_number,
                $deliverable->title
            ), $deliverable, ['version_id' => $version->id]);

            return $this->loadDeliverable($deliverable);
        });
    }

    public function approveDeliverable(MarketingDeliverable $deliverable, array $data, User $actor): MarketingDeliverable
    {
        return DB::transaction(function () use ($deliverable, $data, $actor) {
            $version = $deliverable->currentVersion ?: $deliverable->versions()->latest('version_number')->first();
            if (! $version) {
                throw ValidationException::withMessages([
                    'review_notes' => ['A deliverable cannot be approved without a submitted version.'],
                ]);
            }

            $version->update([
                'approval_status' => 'approved',
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
            ]);

            $deliverable->update([
                'status' => MarketingDeliverableStatus::Approved->value,
                'review_notes' => $data['review_notes'],
                'approved_at' => now(),
            ]);

            $deliverable->assets()->create([
                'deliverable_version_id' => $version->id,
                'asset_type' => $deliverable->deliverable_type,
                'asset_disk' => $version->asset_disk,
                'asset_path' => $version->asset_path,
                'asset_file_name' => $version->asset_file_name,
                'asset_mime_type' => $version->asset_mime_type,
                'asset_file_size' => $version->asset_file_size,
                'reusable' => (bool) ($data['reusable'] ?? false),
            ]);

            $this->synchronizeRequestStatus($deliverable->request);
            $this->recordActivity($deliverable->request, $actor, 'deliverable_approved', sprintf(
                'Deliverable "%s" approved.',
                $deliverable->title
            ), $deliverable, ['version_id' => $version->id]);

            return $this->loadDeliverable($deliverable);
        });
    }

    public function requestAmendments(MarketingDeliverable $deliverable, array $data, User $actor): MarketingDeliverable
    {
        return DB::transaction(function () use ($deliverable, $data, $actor) {
            $version = $deliverable->currentVersion ?: $deliverable->versions()->latest('version_number')->first();
            if ($version) {
                $version->update([
                    'approval_status' => 'changes_requested',
                    'approved_by_user_id' => $actor->id,
                    'approved_at' => now(),
                ]);
            }

            $deliverable->update([
                'status' => MarketingDeliverableStatus::ChangesRequested->value,
                'review_notes' => $data['review_notes'],
            ]);

            $this->synchronizeRequestStatus($deliverable->request);
            $this->recordActivity($deliverable->request, $actor, 'changes_requested', sprintf(
                'Changes requested for deliverable "%s".',
                $deliverable->title
            ), $deliverable);

            return $this->loadDeliverable($deliverable);
        });
    }

    public function publishAsset(MarketingAsset $asset, array $data, User $actor): PublicationRecord
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            $record = $asset->publications()->create([
                'publication_channel' => $data['publication_channel'],
                'published_by_user_id' => $actor->id,
                'published_at' => $data['published_at'] ?? now(),
                'external_reference' => $data['external_reference'] ?? null,
                'publication_notes' => $data['publication_notes'] ?? null,
            ]);

            if (filled($data['metrics']['metric_date'] ?? null)) {
                $record->metricSnapshots()->create($data['metrics']);
            }

            $asset->deliverable->update([
                'status' => MarketingDeliverableStatus::Published->value,
                'published_at' => $record->published_at,
            ]);

            $this->synchronizeRequestStatus($asset->deliverable->request);
            $this->recordActivity($asset->deliverable->request, $actor, 'published', sprintf(
                'Asset for deliverable "%s" published to %s.',
                $asset->deliverable->title,
                $record->publication_channel
            ), $asset->deliverable, ['publication_record_id' => $record->id]);

            return $record->load(['publisher:id,name', 'metricSnapshots']);
        });
    }

    public function archiveAsset(MarketingAsset $asset, User $actor): MarketingAsset
    {
        return DB::transaction(function () use ($asset, $actor) {
            $asset->update([
                'archived_at' => now(),
            ]);

            $this->recordActivity($asset->deliverable->request, $actor, 'asset_archived', sprintf(
                'Asset for deliverable "%s" archived.',
                $asset->deliverable->title
            ), $asset->deliverable, ['asset_id' => $asset->id]);

            return $asset->fresh(['deliverable.request']);
        });
    }

    public function addComment(MarketingRequest $request, User $actor, string $message): MarketingRequest
    {
        return DB::transaction(function () use ($request, $actor, $message) {
            $request->comments()->create([
                'user_id' => $actor->id,
                'message' => $message,
            ]);

            $this->recordActivity($request, $actor, 'comment_added', 'Marketing request comment added.', $request);

            return $this->loadRequest($request->fresh());
        });
    }

    public function uploadRequestDocument(MarketingRequest $request, array $data, User $actor): MarketingRequestDocument
    {
        return DB::transaction(function () use ($request, $data, $actor) {
            /** @var UploadedFile $file */
            $file = $data['file'];
            $path = $file->store("marketing-requests/{$request->id}", 'local');

            $document = $request->documents()->create([
                'uploaded_by_user_id' => $actor->id,
                'title' => $data['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'document_kind' => $data['document_kind'],
                'notes' => $data['notes'] ?? null,
                'disk' => 'local',
                'path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);

            $this->recordActivity($request, $actor, 'document_uploaded', sprintf(
                'Document "%s" uploaded to the marketing request.',
                $document->title
            ), $request, ['document_id' => $document->id]);

            return $document->load('uploader:id,name');
        });
    }

    public function downloadRequestDocument(MarketingRequestDocument $document)
    {
        return Storage::disk($document->disk)->download($document->path, $document->file_name);
    }

    public function importMetricSnapshots(UploadedFile $file, User $actor): int
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => ['Unable to read the uploaded CSV file.'],
            ]);
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => ['The uploaded CSV file is empty.'],
            ]);
        }

        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers);
        $imported = 0;

        DB::transaction(function () use ($handle, $headers, &$imported, $actor) {
            while (($row = fgetcsv($handle)) !== false) {
                $payload = array_combine($headers, $row);
                if (! is_array($payload) || ! filled($payload['publication_record_id'] ?? null) || ! filled($payload['metric_date'] ?? null)) {
                    continue;
                }

                $record = PublicationRecord::query()->find((int) $payload['publication_record_id']);
                if (! $record) {
                    continue;
                }

                $metricDate = Carbon::parse((string) $payload['metric_date'])->toDateString();

                $snapshot = MetricSnapshot::query()
                    ->where('publication_record_id', $record->id)
                    ->whereDate('metric_date', $metricDate)
                    ->first();

                $snapshotPayload = [
                    'impressions' => $this->nullableInt($payload['impressions'] ?? null),
                    'reach' => $this->nullableInt($payload['reach'] ?? null),
                    'engagements' => $this->nullableInt($payload['engagements'] ?? null),
                    'clicks' => $this->nullableInt($payload['clicks'] ?? null),
                    'sessions' => $this->nullableInt($payload['sessions'] ?? null),
                    'conversions' => $this->nullableInt($payload['conversions'] ?? null),
                    'followers' => $this->nullableInt($payload['followers'] ?? null),
                ];

                if ($snapshot) {
                    $snapshot->update($snapshotPayload);
                } else {
                    $record->metricSnapshots()->create([
                        'metric_date' => $metricDate,
                        ...$snapshotPayload,
                    ]);
                }

                $this->recordActivity($record->asset->deliverable->request, $actor, 'metrics_imported', sprintf(
                    'Metrics imported for publication record %d on %s.',
                    $record->id,
                    $metricDate
                ), $record->asset->deliverable, ['publication_record_id' => $record->id]);

                $imported++;
            }
        });

        fclose($handle);

        return $imported;
    }

    protected function visibleRequestQuery(Builder $query, User $actor): Builder
    {
        $departmentId = (int) ($actor->staffMember?->department_id ?? 0);

        return $query->where(function (Builder $builder) use ($actor, $departmentId) {
            $builder->where('requester_user_id', $actor->id)
                ->orWhere('approver_user_id', $actor->id);

            if ($this->governance->belongsToMarketing($actor) && $departmentId > 0) {
                $builder->orWhere('owner_department_id', $departmentId);
            }

            if ($this->governance->belongsToMarketing($actor) && $departmentId === 0) {
                $builder->orWhereNotNull('owner_department_id');
            }
        });
    }

    protected function visibleDeliverableQuery(Builder $query, User $actor): Builder
    {
        return $query->whereHas('request', fn (Builder $requestQuery) => $this->visibleRequestQuery($requestQuery, $actor));
    }

    protected function applyRequestFilters(Builder $query, array $filters): Builder
    {
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('objective', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    protected function loadDeliverable(MarketingDeliverable $deliverable): MarketingDeliverable
    {
        return $deliverable->load([
            'request',
            'assignee:id,name',
            'versions.uploader:id,name',
            'versions.approver:id,name',
            'assets.version',
            'assets.publications.metricSnapshots',
            'assets.publications.publisher:id,name',
        ]);
    }

    protected function synchronizeRequestStatus(MarketingRequest $request): void
    {
        $statuses = $request->deliverables()->pluck('status');

        if ($statuses->isEmpty()) {
            $request->update(['status' => MarketingRequestStatus::Draft->value]);
            return;
        }

        if ($statuses->every(fn ($status) => $status === MarketingDeliverableStatus::Published->value || $status === MarketingDeliverableStatus::Approved->value)) {
            $request->update(['status' => MarketingRequestStatus::Completed->value]);
            return;
        }

        if ($statuses->contains(MarketingDeliverableStatus::ChangesRequested->value)) {
            $request->update(['status' => MarketingRequestStatus::InProduction->value]);
            return;
        }

        if ($statuses->contains(MarketingDeliverableStatus::InternalReview->value)) {
            $request->update(['status' => MarketingRequestStatus::InReview->value]);
            return;
        }

        if ($statuses->contains(MarketingDeliverableStatus::Approved->value)) {
            $request->update(['status' => MarketingRequestStatus::PartiallyApproved->value]);
            return;
        }

        if ($statuses->contains(MarketingDeliverableStatus::InProgress->value) || $statuses->contains(MarketingDeliverableStatus::Assigned->value)) {
            $request->update(['status' => MarketingRequestStatus::InProduction->value]);
            return;
        }

        $request->update(['status' => MarketingRequestStatus::Planned->value]);
    }

    protected function recordActivity(MarketingRequest $request, User $actor, string $action, string $summary, object $subject, array $meta = []): MarketingActivity
    {
        return $request->activities()->create([
            'subject_type' => $subject::class,
            'subject_id' => $subject->id ?? null,
            'actor_user_id' => $actor->id,
            'action' => $action,
            'summary' => $summary,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
