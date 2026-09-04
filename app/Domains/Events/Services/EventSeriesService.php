<?php

namespace App\Domains\Events\Services;

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Services\DocumentAccessService;
use App\Domains\Documents\Services\DocumentFolderService;
use App\Domains\Documents\Services\DocumentLinkService;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventSeries;
use App\Domains\Events\Models\EventSeriesAsset;
use App\Domains\Events\Models\EventTask;
use App\Domains\Events\Models\EventWorkstream;
use App\Domains\Events\Repositories\EventSeriesRepositoryInterface;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventSeriesService
{
    protected const SERIES_DEFAULT_FOLDERS = [
        'Brand Identity',
        'Logos',
        'Brand Guidelines',
        'Historical Posters',
        'Reusable Artwork',
        'Sponsor Materials',
        'Programme Templates',
        'Media Archive',
        'Working Files',
        'Linked Assets',
    ];

    public function __construct(
        protected EventSeriesRepositoryInterface $repository,
        protected EventService $eventService,
        protected DocumentFolderService $documentFolderService,
        protected DocumentAccessService $documentAccessService,
        protected DocumentLinkService $documentLinkService,
    ) {}

    public function allWithSummary(): Collection
    {
        return $this->repository->allWithSummary();
    }

    public function createSeries(array $data, ?User $actor = null): EventSeries
    {
        return DB::transaction(function () use ($data, $actor) {
            $series = $this->repository->create($this->normalizeSeriesPayload($data, $actor));
            $this->createDefaultSeriesFolders($series, $actor);

            return $series->fresh(['events', 'assets.document.folder']);
        });
    }

    public function findBySlugOrKey(string $value): ?EventSeries
    {
        return $this->repository->findBySlugOrKey($value);
    }

    public function resolveSeriesForLegacyKey(string $value): ?EventSeries
    {
        return $this->findBySlugOrKey($this->normalizeKey($value))
            ?? $this->findBySlugOrKey($value);
    }

    public function seriesOverview(EventSeries|string $series): array
    {
        $model = $series instanceof EventSeries ? $series : $this->resolveSeriesForLegacyKey($series);

        if (! $model) {
            abort(404);
        }

        $model->loadMissing(['events.owner', 'events.participants', 'events.workstreams.tasks.attachments', 'events.workstreams.tasks.submittedBy', 'events.workstreams.tasks.reviewedBy', 'events.outcomeReport', 'events.closureReport', 'assets.document.folder']);
        $events = $model->events;
        $latest = $events->sortByDesc(fn (Event $event) => $event->event_year ?? 0)->first();
        $years = $events->pluck('event_year')->filter()->sort()->values();
        $root = $this->seriesRootFolder($model);
        $repositoryFiles = $this->seriesRepositoryFiles($model);

        return [
            'id' => $model->id,
            'name' => $model->name,
            'title' => $model->name,
            'slug' => $model->slug,
            'series_key' => $model->series_key,
            'description' => $model->description,
            'objectives' => $model->objectives,
            'default_title_pattern' => $model->default_title_pattern,
            'default_event_type' => $model->default_event_type,
            'event_type' => $model->default_event_type,
            'default_format' => $model->default_format,
            'default_theme' => $model->default_theme,
            'theme' => $model->default_theme,
            'track_name' => null,
            'status' => $model->status,
            'next_iteration_year' => $this->nextIterationYear($model),
            'document_folder' => $root ? [
                'id' => $root->id,
                'name' => $root->name,
                'href' => route('organization.document-library.index', ['folder' => $root->id]),
            ] : null,
            'assets' => $model->assets->map(fn (EventSeriesAsset $asset) => $this->mapAsset($asset))->values(),
            'repository_files' => $repositoryFiles,
            'stats' => [
                'iterations' => $events->count(),
                'years_run' => $events->count(),
                'year_range' => $years->isEmpty() ? null : $years->first().'-'.$years->last(),
                'latest_year' => $latest?->event_year,
                'completed_events' => $events->where('status', 'completed')->count(),
                'planned_events' => $events->where('status', 'planned')->count(),
                'active_events' => $events->where('status', 'active')->count(),
                'open_events' => $events->where('status', 'open_for_registration')->count(),
                'total_participants' => $events->sum(fn (Event $event) => $event->participants->count()),
                'total_attendees' => $events->sum(fn (Event $event) => $event->participants->whereNotIn('category', ['speaker', 'facilitator'])->count()),
                'total_speakers' => $events->sum(fn (Event $event) => $event->participants->whereIn('category', ['speaker', 'facilitator'])->count()),
            ],
            'latest_iteration' => $latest ? $this->mapIterationSummary($latest) : null,
            'years' => $events->map(fn (Event $event) => $this->mapIterationSummary($event))->values(),
        ];
    }

    public function createIterationFromPrevious(EventSeries $series, array $data, User $actor): Event
    {
        return DB::transaction(function () use ($series, $data, $actor) {
            $lockedSeries = EventSeries::query()->whereKey($series->id)->lockForUpdate()->firstOrFail();
            $year = (int) $data['event_year'];

            if ($lockedSeries->events()->where('event_year', $year)->exists()) {
                throw ValidationException::withMessages([
                    'event_year' => ["{$lockedSeries->name} already has an iteration for {$year}."],
                ]);
            }

            $sourceEvent = $this->resolveSourceEvent($lockedSeries, $data);
            $payload = $this->iterationPayload($lockedSeries, $sourceEvent, $data, $year);
            $partnerIds = $sourceEvent && ($data['copy_partners'] ?? false)
                ? $sourceEvent->partners()->pluck('stakeholders.id')->all()
                : [];

            $event = $this->eventService->createEvent([
                ...$payload,
                'partner_stakeholder_ids' => $partnerIds,
                '_skip_planning_template' => $sourceEvent && ($data['copy_workstreams'] ?? true),
            ]);

            if ($sourceEvent && ($data['copy_workstreams'] ?? true)) {
                $this->copyPlanningTemplates($sourceEvent, $event, (bool) ($data['copy_task_templates'] ?? true));
            }

            return $event->fresh(['eventSeries', 'partners', 'workstreams.tasks']);
        }, 5);
    }

    public function classifyAsset(EventSeries $series, array $data, User $actor): EventSeriesAsset
    {
        $document = DocumentFile::query()->with('folder')->findOrFail((int) $data['document_file_id']);

        if (! $this->documentAccessService->canViewFile($actor, $document)) {
            abort(403);
        }

        if ($document->folder?->owner_type !== EventSeries::class || (int) $document->folder?->owner_id !== (int) $series->id) {
            throw ValidationException::withMessages([
                'document_file_id' => ['Choose a file from this event line repository.'],
            ]);
        }

        $asset = EventSeriesAsset::query()->updateOrCreate(
            [
                'event_series_id' => $series->id,
                'document_file_id' => $document->id,
                'asset_type' => $data['asset_type'],
            ],
            [
                'label' => $data['label'] ?? $document->title,
                'year' => $data['year'] ?? null,
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'display_order' => (int) ($data['display_order'] ?? 1),
                'created_by_user_id' => $actor->id,
            ]
        );

        $this->documentLinkService->link($document, EventSeries::class, $series->id, 'reference', $actor);

        return $asset->fresh('document.folder');
    }

    public function createDefaultSeriesFolders(EventSeries $series, ?User $actor = null): void
    {
        DB::transaction(function () use ($series, $actor) {
            $eventsRoot = $this->documentFolderService->ensureLibraryGroupForOwner('Events', $actor);
            $root = DocumentFolder::query()->firstOrCreate(
                [
                    'parent_id' => $eventsRoot->id,
                    'owner_type' => EventSeries::class,
                    'owner_id' => $series->id,
                    'folder_type' => DocumentFolder::TYPE_EVENT_SERIES_ROOT,
                ],
                [
                    'name' => $series->name,
                    'created_by' => $actor?->id,
                ]
            );

            if ($root->name !== $series->name) {
                $root->update(['name' => $series->name]);
            }

            $this->documentFolderService->ensureOwnedChildFoldersForOwner($root, self::SERIES_DEFAULT_FOLDERS, $actor);
        });
    }

    public function backfillFromLegacyKeys(bool $dryRun = false): array
    {
        $events = Event::query()
            ->whereNotNull('annual_series_key')
            ->where('annual_series_key', '!=', '')
            ->orderBy('annual_series_key')
            ->orderBy('event_year')
            ->get();

        $duplicates = $events
            ->filter(fn (Event $event) => $event->event_year !== null)
            ->groupBy(fn (Event $event) => $this->normalizeKey($event->annual_series_key).'|'.$event->event_year)
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(fn (Collection $group) => $group->pluck('id')->values()->all())
            ->values()
            ->all();

        if ($duplicates !== []) {
            return [
                'created_series' => 0,
                'linked_events' => 0,
                'skipped_events' => 0,
                'duplicates' => $duplicates,
                'dry_run' => $dryRun,
            ];
        }

        $created = 0;
        $linked = 0;
        $skipped = 0;

        DB::transaction(function () use ($events, $dryRun, &$created, &$linked, &$skipped) {
            foreach ($events->groupBy(fn (Event $event) => $this->normalizeKey($event->annual_series_key)) as $key => $group) {
                $valid = $group->filter(fn (Event $event) => $event->event_year !== null);
                $skipped += $group->count() - $valid->count();

                if ($valid->isEmpty()) {
                    continue;
                }

                if ($dryRun) {
                    $created++;
                    $linked += $valid->whereNull('event_series_id')->count();
                    continue;
                }

                $lead = $valid->sortByDesc(fn (Event $event) => $event->event_year)->first();
                $series = EventSeries::query()->firstOrCreate(
                    ['series_key' => $key],
                    [
                        'name' => $this->seriesNameFromEvent($lead),
                        'slug' => $this->uniqueSlug($key),
                        'description' => $lead?->description,
                        'objectives' => $lead?->objectives,
                        'default_title_pattern' => $lead?->title ? preg_replace('/\b(20\d{2}|19\d{2})\b/', '{year}', $lead->title) : null,
                        'default_event_type' => $lead?->event_type,
                        'default_format' => $lead?->event_format,
                        'default_theme' => $lead?->theme,
                        'status' => EventSeries::STATUS_ACTIVE,
                    ]
                );

                if ($series->wasRecentlyCreated) {
                    $created++;
                    $this->createDefaultSeriesFolders($series);
                }

                foreach ($valid as $event) {
                    if ($event->event_series_id) {
                        continue;
                    }

                    $event->update(['event_series_id' => $series->id]);
                    $linked++;
                }
            }
        }, 5);

        return [
            'created_series' => $created,
            'linked_events' => $linked,
            'skipped_events' => $skipped,
            'duplicates' => [],
            'dry_run' => $dryRun,
        ];
    }

    protected function normalizeSeriesPayload(array $data, ?User $actor): array
    {
        $key = $this->normalizeKey($data['series_key'] ?? $data['name']);
        $slug = $this->normalizeKey($data['slug'] ?? $key);

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'series_key' => $key,
            'description' => $data['description'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'default_title_pattern' => $data['default_title_pattern'] ?? $data['name'].' {year}',
            'default_event_type' => $data['default_event_type'] ?? null,
            'default_format' => $data['default_format'] ?? null,
            'default_theme' => $data['default_theme'] ?? null,
            'status' => $data['status'] ?? EventSeries::STATUS_ACTIVE,
            'created_by_user_id' => $actor?->id,
            'updated_by_user_id' => $actor?->id,
        ];
    }

    protected function normalizeKey(string $value): string
    {
        return Str::slug(trim($value));
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = $this->normalizeKey($base);
        $candidate = $slug;
        $suffix = 2;

        while (EventSeries::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix++;
        }

        return $candidate;
    }

    protected function resolveSourceEvent(EventSeries $series, array $data): ?Event
    {
        return match ($data['source']) {
            'selected_iteration' => $series->events()->with(['partners', 'workstreams.tasks'])->findOrFail((int) $data['source_event_id']),
            'latest_iteration' => $series->events()->with(['partners', 'workstreams.tasks'])->orderByDesc('event_year')->orderByDesc('start_date')->first(),
            default => null,
        };
    }

    protected function iterationPayload(EventSeries $series, ?Event $sourceEvent, array $data, int $year): array
    {
        return [
            'event_series_id' => $series->id,
            'title' => ($data['title'] ?? null) ?: $this->titleForYear($series, $sourceEvent, $year),
            'event_type' => $sourceEvent?->event_type ?? $series->default_event_type,
            'event_format' => $sourceEvent?->event_format ?? $series->default_format,
            'annual_series_key' => $series->series_key,
            'event_year' => $year,
            'is_annual' => true,
            'theme' => ($data['theme'] ?? null) ?: ($sourceEvent?->theme ?? $series->default_theme),
            'track_name' => $sourceEvent?->track_name,
            'location' => ($data['location'] ?? null) ?: $sourceEvent?->location,
            'venue_name' => ($data['venue_name'] ?? null) ?: null,
            'venue_address' => null,
            'venue_contact_person' => null,
            'venue_contact_phone' => null,
            'venue_contact_email' => null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'status' => 'planned',
            'description' => $sourceEvent?->description ?? $series->description,
            'objectives' => $sourceEvent?->objectives ?? $series->objectives,
            'technical_requirements' => $sourceEvent?->technical_requirements,
            'registration_link' => null,
            'zoom_join_url' => null,
            'zoom_host_url' => null,
            'zoom_meeting_id' => null,
            'zoom_passcode' => null,
            'expected_attendees' => $sourceEvent?->expected_attendees,
            'owner_staff_member_id' => $sourceEvent?->owner_staff_member_id,
        ];
    }

    protected function titleForYear(EventSeries $series, ?Event $sourceEvent, int $year): string
    {
        $pattern = $series->default_title_pattern ?: ($sourceEvent?->title ?: $series->name.' {year}');

        if (str_contains($pattern, '{year}')) {
            return str_replace('{year}', (string) $year, $pattern);
        }

        return preg_replace('/\b(20\d{2}|19\d{2})\b/', (string) $year, $pattern) ?: "{$series->name} {$year}";
    }

    protected function copyPlanningTemplates(Event $sourceEvent, Event $targetEvent, bool $copyTasks): void
    {
        $sourceEvent->loadMissing('workstreams.tasks');

        foreach ($sourceEvent->workstreams as $sourceWorkstream) {
            $targetWorkstream = $targetEvent->workstreams()->create([
                'name' => $sourceWorkstream->name,
                'description' => $sourceWorkstream->description,
                'sort_order' => $sourceWorkstream->sort_order,
            ]);

            if (! $copyTasks) {
                continue;
            }

            foreach ($sourceWorkstream->tasks as $task) {
                $targetWorkstream->tasks()->create($this->resetTaskPayload($task));
            }
        }
    }

    protected function resetTaskPayload(EventTask $task): array
    {
        return [
            'phase' => $task->phase,
            'task_group' => $task->task_group,
            'is_custom' => $task->is_custom,
            'duty' => $task->duty,
            'due_date' => null,
            'responsible_person' => $task->responsible_person,
            'outcome' => null,
            'status' => 'pending',
            'comment' => null,
            'evidence_disk' => null,
            'evidence_path' => null,
            'evidence_file_name' => null,
            'evidence_mime_type' => null,
            'evidence_file_size' => null,
            'evidence_url' => null,
            'completed_at' => null,
            'completion_status' => 'not_submitted',
            'submitted_for_verification_at' => null,
            'submitted_by_user_id' => null,
            'manager_review_notes' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
            'returned_for_amendments_at' => null,
            'sort_order' => $task->sort_order,
        ];
    }

    protected function nextIterationYear(EventSeries $series): int
    {
        $latest = $series->events->max('event_year');

        return $latest ? ((int) $latest + 1) : (int) now()->year;
    }

    protected function mapIterationSummary(Event $event): array
    {
        $participantSummary = $this->eventService->participantSummaryForSeries($event);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'event_year' => $event->event_year,
            'status' => $event->status,
            'location' => $event->location,
            'venue_name' => $event->venue_name,
            'start_date' => $event->start_date?->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'owner_name' => $event->owner ? trim($event->owner->first_name.' '.$event->owner->last_name) : null,
            'participant_count' => $participantSummary['participant_count'],
            'speaker_count' => $participantSummary['speaker_count'],
            'attendee_count' => $participantSummary['attendee_count'],
            'planning_summary' => $this->eventService->planningSummaryForSeries($event),
            'event_day_summary' => $this->eventService->eventDaySummaryForSeries($event),
            'outcome_report' => $this->eventService->outcomeReportForSeries($event),
            'report_url' => route('events.report.pdf', $event->id),
            'show_url' => route('events.show', $event->id),
        ];
    }

    protected function mapAsset(EventSeriesAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'asset_type' => $asset->asset_type,
            'label' => $asset->label,
            'year' => $asset->year,
            'is_featured' => $asset->is_featured,
            'display_order' => $asset->display_order,
            'document' => $asset->document ? [
                'id' => $asset->document->id,
                'title' => $asset->document->title,
                'original_name' => $asset->document->original_name,
                'mime_type' => $asset->document->mime_type,
                'status' => $asset->document->status,
                'download_url' => route('organization.document-library.files.download', $asset->document->id),
                'preview_url' => route('organization.document-library.files.preview', $asset->document->id),
            ] : null,
        ];
    }

    protected function seriesRootFolder(EventSeries $series): ?DocumentFolder
    {
        return DocumentFolder::query()
            ->where('owner_type', EventSeries::class)
            ->where('owner_id', $series->id)
            ->where('folder_type', DocumentFolder::TYPE_EVENT_SERIES_ROOT)
            ->first();
    }

    protected function seriesRepositoryFiles(EventSeries $series): array
    {
        $folderIds = DocumentFolder::query()
            ->where('owner_type', EventSeries::class)
            ->where('owner_id', $series->id)
            ->pluck('id');

        if ($folderIds->isEmpty()) {
            return [];
        }

        return DocumentFile::query()
            ->whereIn('folder_id', $folderIds)
            ->orderBy('title')
            ->get()
            ->map(fn (DocumentFile $file) => [
                'id' => $file->id,
                'title' => $file->title,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'status' => $file->status,
            ])
            ->values()
            ->all();
    }

    protected function seriesNameFromEvent(?Event $event): string
    {
        if (! $event) {
            return 'Event Series';
        }

        return trim(preg_replace('/\b(20\d{2}|19\d{2})\b/', '', $event->title)) ?: Str::headline($event->annual_series_key);
    }
}
