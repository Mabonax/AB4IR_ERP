<?php

namespace App\Domains\Events\Services;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventOutcomeReport;
use App\Domains\Events\Models\EventParticipant;
use App\Domains\Events\Models\EventTask;
use App\Domains\Events\Models\EventWorkstream;
use App\Domains\Events\Repositories\EventRepositoryInterface;
use App\Domains\Staff\Models\StaffMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class EventService
{
    protected array $phaseLabels = [
        'pre_event' => 'Pre-Event',
        'preparations' => 'Preparations',
        'event_day' => 'Event Day',
        'post_event' => 'Post-Event',
    ];

    protected array $participantCategoryLabels = [
        'speaker' => 'Speakers',
        'facilitator' => 'Facilitators',
        'attendee' => 'Attendees',
        'exhibitor' => 'Exhibitors',
        'sponsor' => 'Sponsors',
        'partner' => 'Partners',
        'media_house' => 'Media Houses',
        'vip' => 'VIPs',
        'team_board' => 'Team and Board',
    ];

    protected array $departmentTaskGroups = [
        'Administration' => [
            'Venue',
            'Speakers',
            'Participants',
            'JOC / Compliance',
            'Transport / Accommodation',
            'General Logistics',
        ],
        'Marketing' => [
            'Graphic Design',
            'Content & Communications',
            'Outreach & Stakeholder Communication',
        ],
        'Technical' => [
            'Streaming & Virtual Access',
            'AV / Equipment',
            'Media Capture',
            'Registration Systems',
            'Presentation Support',
        ],
        'Impact & Reporting' => [
            'Impact Measurement',
            'Reporting',
            'Opportunities & Follow-up',
        ],
    ];

    public function __construct(
        protected EventRepositoryInterface $repository
    ) {}

    public function paginateEvents(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getEvent(int $id): Event
    {
        return $this->repository->find($id) ?? abort(404);
    }

    public function createEvent(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            $partnerIds = array_values(array_filter(array_map('intval', $data['partner_stakeholder_ids'] ?? [])));
            unset($data['partner_stakeholder_ids']);

            $event = $this->repository->create($data);

            if ($partnerIds !== []) {
                $event->partners()->sync($partnerIds);
            }

            $this->ensurePlanningTemplate($event);

            return $event->fresh(['owner', 'participants', 'partners', 'workstreams.tasks']);
        });
    }

    public function updateEvent(int $id, array $data): Event
    {
        $event = $this->getEvent($id);

        return DB::transaction(function () use ($event, $data) {
            $partnerIds = array_values(array_filter(array_map('intval', $data['partner_stakeholder_ids'] ?? [])));
            unset($data['partner_stakeholder_ids']);

            $updated = $this->repository->update($event, $data);
            $updated->partners()->sync($partnerIds);

            return $updated->fresh(['owner', 'participants', 'partners', 'workstreams.tasks']);
        });
    }

    public function deleteEvent(int $id): bool
    {
        $event = $this->getEvent($id);

        return $this->repository->delete($event);
    }

    public function addSpeaker(Event $event, array $data): EventParticipant
    {
        return $this->addParticipant($event, [
            ...$data,
            'category' => $data['category'] ?? 'speaker',
        ]);
    }

    public function removeSpeaker(Event $event, int $speakerId): void
    {
        $speaker = $event->participants()
            ->whereIn('category', ['speaker', 'facilitator'])
            ->findOrFail($speakerId);
        $speaker->delete();
    }

    public function addAttendee(Event $event, array $data): EventParticipant
    {
        return $this->addParticipant($event, [
            ...$data,
            'category' => $data['category'] ?? 'attendee',
        ]);
    }

    public function updateAttendeeStatus(Event $event, int $attendeeId, string $status): EventParticipant
    {
        $attendee = $event->participants()->findOrFail($attendeeId);
        $attendee->update([
            'attendance_status' => $status,
            'checked_in_at' => $status === 'checked_in' ? now() : null,
        ]);

        return $attendee->fresh();
    }

    public function removeAttendee(Event $event, int $attendeeId): void
    {
        $attendee = $event->participants()->findOrFail($attendeeId);
        $attendee->delete();
    }

    public function addParticipant(Event $event, array $data): EventParticipant
    {
        return DB::transaction(function () use ($event, $data) {
            return $event->participants()->create([
                'category' => $data['category'],
                'name' => $data['name'],
                'surname' => $data['surname'] ?? null,
                'title' => $data['title'] ?? null,
                'organization_name' => $data['organization_name'] ?? null,
                'topic' => $data['topic'] ?? null,
                'bio' => $data['bio'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'] ?? null,
                'attendance_type' => $data['attendance_type'] ?? null,
                'attendance_status' => $data['attendance_status'] ?? 'registered',
                'checked_in_at' => ($data['attendance_status'] ?? 'registered') === 'checked_in' ? now() : null,
                'notes' => $data['notes'] ?? null,
                'sort_order' => $data['sort_order'] ?? (($event->participants()->where('category', $data['category'])->max('sort_order') ?? 0) + 1),
            ]);
        });
    }

    public function updateParticipant(Event $event, int $participantId, array $data): EventParticipant
    {
        $participant = $event->participants()->findOrFail($participantId);

        $participant->update([
            'category' => $data['category'],
            'name' => $data['name'],
            'surname' => $data['surname'] ?? null,
            'title' => $data['title'] ?? null,
            'organization_name' => $data['organization_name'] ?? null,
            'topic' => $data['topic'] ?? null,
            'bio' => $data['bio'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? null,
            'attendance_type' => $data['attendance_type'] ?? null,
            'attendance_status' => $data['attendance_status'] ?? $participant->attendance_status,
            'checked_in_at' => ($data['attendance_status'] ?? $participant->attendance_status) === 'checked_in'
                ? ($participant->checked_in_at ?? now())
                : null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => $data['sort_order'] ?? $participant->sort_order,
        ]);

        return $participant->fresh();
    }

    public function removeParticipant(Event $event, int $participantId): void
    {
        $participant = $event->participants()->findOrFail($participantId);
        $participant->delete();
    }

    public function updateParticipantStatus(Event $event, int $participantId, string $status): EventParticipant
    {
        $participant = $event->participants()->findOrFail($participantId);
        $participant->update([
            'attendance_status' => $status,
            'checked_in_at' => $status === 'checked_in' ? ($participant->checked_in_at ?? now()) : null,
        ]);

        return $participant->fresh();
    }

    public function importParticipantsFromFile(Event $event, UploadedFile $file, ?string $categoryContext = null): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->parseParticipantCsvFile($file),
            'xlsx' => $this->parseParticipantXlsxFile($file),
            default => throw ValidationException::withMessages([
                'file' => ['Unsupported file format. Use CSV or XLSX.'],
            ]),
        };

        $summary = [
            'processed' => 0,
            'created' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $summary['processed']++;

            try {
                $payload = $this->mapParticipantImportRow($row, $categoryContext);

                if ($this->participantExists($event, $payload)) {
                    $summary['duplicates']++;

                    continue;
                }

                $this->addParticipant($event, $payload);
                $summary['created']++;
            } catch (\Throwable $exception) {
                $summary['errors'][] = "Row {$line}: {$exception->getMessage()}";
            }
        }

        return $summary;
    }

    public function upsertOutcomeReport(Event $event, array $data): EventOutcomeReport
    {
        $reporter = StaffMember::query()
            ->where('user_id', auth()->id())
            ->first();

        return DB::transaction(function () use ($event, $data, $reporter) {
            $report = $event->outcomeReport()->updateOrCreate(
                ['event_id' => $event->id],
                [
                    'summary' => $data['summary'] ?? null,
                    'highlights' => $data['highlights'] ?? null,
                    'opportunities_created' => $data['opportunities_created'] ?? null,
                    'partnerships_formed' => $data['partnerships_formed'] ?? null,
                    'training_opportunities' => $data['training_opportunities'] ?? null,
                    'media_coverage' => $data['media_coverage'] ?? null,
                    'statistics_summary' => $data['statistics_summary'] ?? null,
                    'thank_you_status' => $data['thank_you_status'] ?? null,
                    'follow_up_actions' => $data['follow_up_actions'] ?? null,
                    'report_status' => $data['report_status'],
                    'reported_by_staff_member_id' => $reporter?->id,
                    'reported_at' => now(),
                ]
            );

            return $report->fresh('reporter');
        });
    }

    public function addWorkstream(Event $event, array $data): EventWorkstream
    {
        return DB::transaction(function () use ($event, $data) {
            return $event->workstreams()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? (($event->workstreams()->max('sort_order') ?? 0) + 1),
            ]);
        });
    }

    public function updateWorkstream(Event $event, int $workstreamId, array $data): EventWorkstream
    {
        $workstream = $event->workstreams()->findOrFail($workstreamId);
        $workstream->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $workstream->sort_order,
        ]);

        return $workstream->fresh('tasks');
    }

    public function removeWorkstream(Event $event, int $workstreamId): void
    {
        $workstream = $event->workstreams()->findOrFail($workstreamId);
        $workstream->delete();
    }

    public function addTask(Event $event, array $data, ?UploadedFile $evidenceFile = null): EventTask
    {
        $workstream = $event->workstreams()->findOrFail((int) $data['event_workstream_id']);

        return DB::transaction(function () use ($event, $workstream, $data, $evidenceFile) {
            $task = $workstream->tasks()->create([
                'phase' => $data['phase'],
                'task_group' => $data['task_group'],
                'is_custom' => $data['is_custom'] ?? true,
                'duty' => $data['duty'],
                'due_date' => $data['due_date'] ?? null,
                'responsible_person' => $data['responsible_person'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'status' => $data['status'],
                'comment' => $data['comment'] ?? null,
                'evidence_url' => $data['evidence_url'] ?? null,
                'completed_at' => ($data['status'] ?? null) === 'completed' ? now() : null,
                'sort_order' => $data['sort_order'] ?? (($workstream->tasks()->where('phase', $data['phase'])->max('sort_order') ?? 0) + 1),
            ]);

            $this->syncTaskEvidence($event, $task, $data, $evidenceFile);

            return $task->fresh('workstream');
        });
    }

    public function updateTask(Event $event, int $taskId, array $data, ?UploadedFile $evidenceFile = null): EventTask
    {
        $task = EventTask::query()
            ->whereHas('workstream', fn ($query) => $query->where('event_id', $event->id))
            ->findOrFail($taskId);

        $event->workstreams()->findOrFail((int) $data['event_workstream_id']);

        return DB::transaction(function () use ($event, $task, $data, $evidenceFile) {
            $task->update([
                'event_workstream_id' => (int) $data['event_workstream_id'],
                'phase' => $data['phase'],
                'task_group' => $data['task_group'],
                'is_custom' => $data['is_custom'] ?? $task->is_custom,
                'duty' => $data['duty'],
                'due_date' => $data['due_date'] ?? null,
                'responsible_person' => $data['responsible_person'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'status' => $data['status'],
                'comment' => $data['comment'] ?? null,
                'evidence_url' => $data['evidence_url'] ?? $task->evidence_url,
                'completed_at' => $data['status'] === 'completed'
                    ? ($task->completed_at ?? now())
                    : null,
                'sort_order' => $data['sort_order'] ?? $task->sort_order,
            ]);

            $this->syncTaskEvidence($event, $task, $data, $evidenceFile);

            return $task->fresh('workstream');
        });
    }

    public function removeTask(Event $event, int $taskId): void
    {
        $task = EventTask::query()
            ->whereHas('workstream', fn ($query) => $query->where('event_id', $event->id))
            ->findOrFail($taskId);

        if ($task->evidence_path && $task->evidence_disk) {
            Storage::disk($task->evidence_disk)->delete($task->evidence_path);
        }

        $task->delete();
    }

    public function downloadTaskEvidence(Event $event, int $taskId)
    {
        $task = EventTask::query()
            ->whereHas('workstream', fn ($query) => $query->where('event_id', $event->id))
            ->findOrFail($taskId);

        abort_if(! $task->evidence_path || ! $task->evidence_disk, 404);

        return Storage::disk($task->evidence_disk)->download($task->evidence_path, $task->evidence_file_name);
    }

    public function seriesHistory(Event $event): Collection
    {
        if (! $event->annual_series_key) {
            return collect();
        }

        return $this->repository->seriesHistory($event->annual_series_key);
    }

    public function eventReportPayload(Event $event): array
    {
        $seriesHistory = $this->seriesHistory($event);

        return [
            'event' => $event,
            'summary' => $this->attendanceSummary($event),
            'series_summary' => $this->annualSeriesSummary($event, $seriesHistory),
            'series_history' => $seriesHistory->map(fn (Event $item) => $this->mapSeriesHistoryItem($item))->values()->all(),
            'planning_summary' => $this->planningSummary($event),
            'registers' => $this->participantRegisters($event),
            'event_day_summary' => $this->eventDaySummary($event),
            'outcome_report' => $this->outcomeReportPayload($event),
        ];
    }

    public function seriesOverview(string $seriesKey): array
    {
        $history = $this->repository->seriesHistory($seriesKey);

        if ($history->isEmpty()) {
            abort(404);
        }

        $lead = $history->first();

        return [
            'series_key' => $seriesKey,
            'title' => $lead?->title,
            'event_type' => $lead?->event_type,
            'theme' => $lead?->theme,
            'track_name' => $lead?->track_name,
            'stats' => [
                'years_run' => $history->count(),
                'completed_events' => $history->where('status', 'completed')->count(),
                'active_events' => $history->where('status', 'active')->count(),
                'open_events' => $history->where('status', 'open_for_registration')->count(),
                'total_participants' => $history->sum(fn (Event $item) => $this->participantSummary($item)['participant_count']),
                'total_attendees' => $history->sum(fn (Event $item) => $this->participantSummary($item)['attendee_count']),
                'total_speakers' => $history->sum(fn (Event $item) => $this->participantSummary($item)['speaker_count']),
            ],
            'years' => $history->map(function (Event $item) {
                $summary = $this->participantSummary($item);

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'event_year' => $item->event_year,
                    'status' => $item->status,
                    'location' => $item->location,
                    'venue_name' => $item->venue_name,
                    'start_date' => $item->start_date?->format('Y-m-d'),
                    'end_date' => $item->end_date?->format('Y-m-d'),
                    'owner_name' => $item->owner
                        ? trim($item->owner->first_name.' '.$item->owner->last_name)
                        : null,
                    'participant_count' => $summary['participant_count'],
                    'speaker_count' => $summary['speaker_count'],
                    'attendee_count' => $summary['attendee_count'],
                    'planning_summary' => $this->planningSummary($item),
                    'event_day_summary' => $this->eventDaySummary($item),
                    'outcome_report' => $this->outcomeReportPayload($item),
                ];
            })->values(),
        ];
    }

    public function mapEvent(Event $event): array
    {
        $participantSummary = $this->participantSummary($event);
        $speakerCount = $participantSummary['speaker_count'];
        $attendeeCount = $participantSummary['attendee_count'];
        $checkedInCount = $participantSummary['checked_in_count'];
        $seriesHistory = $this->seriesHistory($event);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'event_type' => $event->event_type,
            'event_format' => $event->event_format,
            'annual_series_key' => $event->annual_series_key,
            'event_year' => $event->event_year,
            'is_annual' => $event->is_annual,
            'theme' => $event->theme,
            'track_name' => $event->track_name,
            'location' => $event->location,
            'venue_name' => $event->venue_name,
            'venue_address' => $event->venue_address,
            'venue_contact_person' => $event->venue_contact_person,
            'venue_contact_phone' => $event->venue_contact_phone,
            'venue_contact_email' => $event->venue_contact_email,
            'start_date' => $event->start_date?->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'status' => $event->status,
            'description' => $event->description,
            'objectives' => $event->objectives,
            'technical_requirements' => $event->technical_requirements,
            'registration_link' => $event->registration_link,
            'zoom_join_url' => $event->zoom_join_url,
            'zoom_host_url' => $event->zoom_host_url,
            'zoom_meeting_id' => $event->zoom_meeting_id,
            'zoom_passcode' => $event->zoom_passcode,
            'expected_attendees' => $event->expected_attendees,
            'owner_staff_member_id' => $event->owner_staff_member_id,
            'owner_name' => $event->owner
                ? trim($event->owner->first_name.' '.$event->owner->last_name)
                : null,
            'partner_stakeholder_ids' => $event->partners->pluck('id')->values(),
            'partner_names' => $event->partners->map(fn ($partner) => trim(($partner->organization_name ?: 'Stakeholder').' - '.$partner->name))->values(),
            'speaker_count' => $speakerCount,
            'attendee_count' => $attendeeCount,
            'participant_count' => $participantSummary['participant_count'],
            'checked_in_count' => $checkedInCount,
            'attendance_summary' => $this->attendanceSummary($event),
            'annual_series_summary' => $this->annualSeriesSummary($event, $seriesHistory),
            'series_history' => $seriesHistory->map(fn (Event $item) => $this->mapSeriesHistoryItem($item))->values(),
            'planning_summary' => $this->planningSummary($event),
            'registers' => $this->participantRegisters($event),
            'event_day_summary' => $this->eventDaySummary($event),
            'outcome_report' => $this->outcomeReportPayload($event),
            'participant_summary' => $participantSummary,
            'participant_categories' => collect($participantSummary['category_counts'])
                ->map(fn ($count, $category) => [
                    'key' => $category,
                    'label' => $this->participantCategoryLabels[$category] ?? str($category)->replace('_', ' ')->title()->toString(),
                    'count' => $count,
                ])
                ->values(),
            'planning_phases' => collect($this->phaseLabels)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
            'workstreams' => $event->workstreams->map(function (EventWorkstream $workstream) {
                $groupOptions = $this->taskGroupsForDepartment($workstream->name);

                return [
                    'id' => $workstream->id,
                    'name' => $workstream->name,
                    'description' => $workstream->description,
                    'sort_order' => $workstream->sort_order,
                    'task_group_options' => $groupOptions,
                    'tasks' => $workstream->tasks->map(function (EventTask $task) {
                        return [
                            'id' => $task->id,
                            'phase' => $task->phase,
                            'phase_label' => $this->phaseLabels[$task->phase] ?? $task->phase,
                            'task_group' => $task->task_group,
                            'is_custom' => $task->is_custom,
                            'duty' => $task->duty,
                            'due_date' => $task->due_date?->format('Y-m-d'),
                            'responsible_person' => $task->responsible_person,
                            'outcome' => $task->outcome,
                            'status' => $task->status,
                            'comment' => $task->comment,
                            'evidence_file_name' => $task->evidence_file_name,
                            'evidence_url' => $task->evidence_url,
                            'has_evidence_file' => filled($task->evidence_path),
                            'completed_at' => $task->completed_at?->toDateTimeString(),
                            'sort_order' => $task->sort_order,
                        ];
                    })->values(),
                ];
            })->values(),
            'participants' => $event->participants->map(fn (EventParticipant $participant) => [
                'id' => $participant->id,
                'category' => $participant->category,
                'category_label' => $this->participantCategoryLabels[$participant->category] ?? str($participant->category)->replace('_', ' ')->title()->toString(),
                'name' => $participant->name,
                'surname' => $participant->surname,
                'title' => $participant->title,
                'organization_name' => $participant->organization_name,
                'topic' => $participant->topic,
                'bio' => $participant->bio,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'role' => $participant->role,
                'attendance_type' => $participant->attendance_type,
                'attendance_status' => $participant->attendance_status,
                'checked_in_at' => $participant->checked_in_at?->toDateTimeString(),
                'notes' => $participant->notes,
                'sort_order' => $participant->sort_order,
            ])->values(),
            'speakers' => $event->participants
                ->filter(fn (EventParticipant $participant) => in_array($participant->category, ['speaker', 'facilitator'], true))
                ->values()
                ->map(fn (EventParticipant $speaker) => [
                    'id' => $speaker->id,
                    'name' => $speaker->name,
                    'surname' => $speaker->surname,
                    'title' => $speaker->title,
                    'organization_name' => $speaker->organization_name,
                    'topic' => $speaker->topic,
                    'bio' => $speaker->bio,
                    'email' => $speaker->email,
                    'phone' => $speaker->phone,
                    'attendance_type' => $speaker->attendance_type,
                    'sort_order' => $speaker->sort_order,
                ]),
            'attendees' => $event->participants
                ->filter(fn (EventParticipant $participant) => ! in_array($participant->category, ['speaker', 'facilitator'], true))
                ->values()
                ->map(fn (EventParticipant $attendee) => [
                    'id' => $attendee->id,
                    'name' => $attendee->name,
                    'surname' => $attendee->surname,
                    'email' => $attendee->email,
                    'phone' => $attendee->phone,
                    'organization_name' => $attendee->organization_name,
                    'role' => $attendee->role,
                    'attendance_type' => $attendee->attendance_type,
                    'attendance_status' => $attendee->attendance_status,
                    'checked_in_at' => $attendee->checked_in_at?->toDateTimeString(),
                    'category' => $attendee->category,
                ]),
        ];
    }

    public function dashboardStats(LengthAwarePaginator $events): array
    {
        $collection = collect($events->items());

        return [
            'total_events' => $collection->count(),
            'planned_events' => $collection->where('status', 'planned')->count(),
            'open_events' => $collection->where('status', 'open_for_registration')->count(),
            'active_events' => $collection->where('status', 'active')->count(),
            'completed_events' => $collection->where('status', 'completed')->count(),
            'annual_events' => $collection->where('is_annual', true)->count(),
            'total_participants' => $collection->sum(fn ($event) => is_array($event) ? ($event['participant_count'] ?? count($event['participants'] ?? [])) : $event->participants->count()),
            'total_attendees' => $collection->sum(fn ($event) => is_array($event) ? ($event['attendee_count'] ?? count($event['attendees'] ?? [])) : $this->participantSummary($event)['attendee_count']),
            'total_speakers' => $collection->sum(fn ($event) => is_array($event) ? ($event['speaker_count'] ?? count($event['speakers'] ?? [])) : $this->participantSummary($event)['speaker_count']),
        ];
    }

    protected function attendanceSummary(Event $event): array
    {
        $attendanceTracked = $this->attendanceTrackedParticipants($event);
        $counts = [
            'registered' => 0,
            'confirmed' => 0,
            'checked_in' => 0,
            'attended' => 0,
            'cancelled' => 0,
        ];

        foreach ($attendanceTracked as $attendee) {
            $status = $attendee->attendance_status ?: 'registered';
            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        $total = $attendanceTracked->count();
        $present = $counts['checked_in'] + $counts['attended'];

        return [
            ...$counts,
            'total' => $total,
            'present' => $present,
            'attendance_rate' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
        ];
    }

    protected function annualSeriesSummary(Event $event, Collection $seriesHistory): array
    {
        if (! $event->annual_series_key || $seriesHistory->isEmpty()) {
            return [
                'series_key' => $event->annual_series_key,
                'years_run' => $event->annual_series_key ? 1 : 0,
                'completed_events' => $event->status === 'completed' ? 1 : 0,
                'total_attendees' => $this->participantSummary($event)['attendee_count'],
                'total_speakers' => $this->participantSummary($event)['speaker_count'],
                'latest_year' => $event->event_year,
            ];
        }

        return [
            'series_key' => $event->annual_series_key,
            'years_run' => $seriesHistory->count(),
            'completed_events' => $seriesHistory->where('status', 'completed')->count(),
            'total_attendees' => $seriesHistory->sum(fn (Event $item) => $this->participantSummary($item)['attendee_count']),
            'total_speakers' => $seriesHistory->sum(fn (Event $item) => $this->participantSummary($item)['speaker_count']),
            'latest_year' => $seriesHistory->first()?->event_year,
        ];
    }

    protected function mapSeriesHistoryItem(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'event_year' => $event->event_year,
            'status' => $event->status,
            'location' => $event->location,
            'start_date' => $event->start_date?->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'attendee_count' => $this->participantSummary($event)['attendee_count'],
            'speaker_count' => $this->participantSummary($event)['speaker_count'],
        ];
    }

    protected function mapParticipantImportRow(array $row, ?string $categoryContext = null): array
    {
        $normalized = [];
        foreach ($row as $header => $value) {
            $normalized[$this->normalizeImportHeader((string) $header)] = $this->normalizeImportValue($value);
        }

        $firstName = $this->firstPresentValue($normalized, [
            'personal details name first name',
            'first name',
            'name first name',
            'given name',
        ]);
        $surname = $this->firstPresentValue($normalized, [
            'personal details name last name',
            'last name',
            'surname',
            'family name',
        ]);
        $fullName = $this->firstPresentValue($normalized, [
            'full name',
            'participant name',
            'name',
            'organisation details full name',
            'partner application full name',
        ]);
        $name = $firstName ?: $fullName;
        if (! $name) {
            throw new RuntimeException("Missing value for 'name'.");
        }

        $rawCategory = $this->firstPresentValue($normalized, [
            'category',
            'participant category',
            'participant type',
            'attendee type',
            'role category',
            'select',
        ]) ?? $categoryContext ?? 'attendee';

        $category = $this->normalizeParticipantCategory($rawCategory);
        if (! array_key_exists($category, $this->participantCategoryLabels)) {
            throw new RuntimeException("Unsupported participant category '{$rawCategory}'.");
        }

        $attendanceStatus = $this->firstPresentValue($normalized, [
            'attendance status',
            'status',
            'registration status',
        ]) ?? 'registered';
        $attendanceStatus = $this->normalizeAttendanceStatus($attendanceStatus);

        $attendanceType = $this->firstPresentValue($normalized, [
            'attendance type',
            'personal details how will you be attending the event',
        ]);

        if ($category === 'attendee' && blank($attendanceType)) {
            throw new RuntimeException("Missing value for 'attendance type' for attendee import.");
        }

        return [
            'category' => $category,
            'name' => $name,
            'surname' => $surname,
            'title' => $this->firstPresentValue($normalized, [
                'personal details name prefix',
                'title',
                'designation',
            ]),
            'organization_name' => $this->firstPresentValue($normalized, [
                'organization',
                'organisation',
                'company',
                'institution',
                'organisation details company organization name',
                'partner application company organization name',
            ]),
            'topic' => $this->firstPresentValue($normalized, ['topic', 'session topic', 'presentation topic']),
            'bio' => $this->firstPresentValue($normalized, ['bio', 'profile', 'biography']),
            'email' => $this->firstPresentValue($normalized, [
                'email',
                'email address',
                'personal details email address',
                'organisation details email address',
                'partner application email address',
            ]),
            'phone' => $this->firstPresentValue($normalized, [
                'phone',
                'mobile',
                'mobile number',
                'contact number',
                'personal details phone',
                'organisation details contact number',
                'partner application contact number',
            ]),
            'role' => $this->firstPresentValue($normalized, [
                'role',
                'position',
                'participant role',
                'organisation details role',
                'partner application role',
                'personal details what best describes you',
            ]),
            'attendance_type' => $attendanceType,
            'attendance_status' => $attendanceStatus,
            'notes' => $this->firstPresentValue($normalized, [
                'notes',
                'comment',
                'comments',
                'remarks',
                'additional comments',
                'personal details additional comments',
                'partner application additional information',
                'organisation details describe your exhibition activation experience',
            ]),
            'sort_order' => $this->toNullableInt($this->firstPresentValue($normalized, ['sort order', 'order'])),
        ];
    }

    protected function parseParticipantCsvFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => ['Could not read CSV file.'],
            ]);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            throw ValidationException::withMessages([
                'file' => ['CSV file is empty.'],
            ]);
        }

        $this->assertParticipantImportHeaders($headers);

        $rows = [];
        while (($line = fgetcsv($handle)) !== false) {
            if ($this->isEmptyImportRow($line)) {
                continue;
            }

            $line = array_pad($line, count($headers), null);
            $rows[] = array_combine($headers, $line);
        }

        fclose($handle);

        return $rows;
    }

    protected function parseParticipantXlsxFile(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'file' => ['Could not open XLSX file.'],
            ]);
        }

        $sheetPath = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheetPath) === false) {
            $zip->close();
            throw ValidationException::withMessages([
                'file' => ['XLSX worksheet not found. Expected sheet1.'],
            ]);
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        $sharedStrings = [];
        if ($sharedStringsXml !== false) {
            $sharedXml = simplexml_load_string($sharedStringsXml);
            if ($sharedXml !== false && isset($sharedXml->si)) {
                foreach ($sharedXml->si as $item) {
                    if (isset($item->t)) {
                        $sharedStrings[] = (string) $item->t;

                        continue;
                    }

                    $text = '';
                    if (isset($item->r)) {
                        foreach ($item->r as $run) {
                            $text .= (string) ($run->t ?? '');
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $xml = simplexml_load_string((string) $sheetXml);
        if ($xml === false) {
            throw ValidationException::withMessages([
                'file' => ['Could not parse XLSX sheet XML.'],
            ]);
        }

        $namespaces = $xml->getNamespaces(true);
        $sheetData = $xml->children($namespaces[''] ?? null)->sheetData ?? null;
        if (! $sheetData) {
            throw ValidationException::withMessages([
                'file' => ['No worksheet data found in XLSX file.'],
            ]);
        }

        $rawRows = [];
        foreach ($sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromReference($ref);
                $type = (string) ($cell['t'] ?? '');
                $cellValue = '';

                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    $cellValue = $type === 's'
                        ? ($sharedStrings[(int) $raw] ?? '')
                        : $raw;
                } elseif (isset($cell->is->t)) {
                    $cellValue = (string) $cell->is->t;
                }

                $values[$columnIndex] = $cellValue;
            }

            if (! empty($values)) {
                ksort($values);
                $rawRows[] = $values;
            }
        }

        if (empty($rawRows)) {
            throw ValidationException::withMessages([
                'file' => ['XLSX file is empty.'],
            ]);
        }

        $headers = array_values($rawRows[0]);
        $this->assertParticipantImportHeaders($headers);

        $rows = [];
        foreach (array_slice($rawRows, 1) as $rawRow) {
            $line = array_values($rawRow);
            if ($this->isEmptyImportRow($line)) {
                continue;
            }

            $line = array_pad($line, count($headers), null);
            $rows[] = array_combine($headers, $line);
        }

        return $rows;
    }

    protected function assertParticipantImportHeaders(array $headers): void
    {
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeImportHeader((string) $header), $headers);

        $hasName = count(array_intersect($normalizedHeaders, [
            'full name',
            'participant name',
            'name',
            'first name',
            'personal details name first name',
            'organisation details full name',
            'partner application full name',
        ])) > 0;
        if (! $hasName) {
            throw ValidationException::withMessages([
                'file' => ['Missing required header for participant name. Expected one of: full name, participant name, name, first name, or the Forminator name fields.'],
            ]);
        }
    }

    protected function normalizeImportHeader(string $header): string
    {
        $header = Str::lower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header) ?? $header;

        return trim($header);
    }

    protected function firstPresentValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                $value = trim((string) $row[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function normalizeParticipantCategory(string $category): string
    {
        $normalized = $this->normalizeImportHeader($category);

        return match ($normalized) {
            'speaker', 'speakers' => 'speaker',
            'facilitator', 'facilitators' => 'facilitator',
            'attendee', 'attendees', 'guest', 'guests' => 'attendee',
            'exhibitor', 'exhibitors', 'exhibitor application' => 'exhibitor',
            'sponsor', 'sponsors' => 'sponsor',
            'partner', 'partners', 'partner application' => 'partner',
            'media', 'media house', 'media houses' => 'media_house',
            'vip', 'vips' => 'vip',
            'team board', 'team and board', 'board', 'team' => 'team_board',
            default => str_replace(' ', '_', $normalized),
        };
    }

    protected function normalizeAttendanceStatus(string $status): string
    {
        $normalized = $this->normalizeImportHeader($status);

        return match ($normalized) {
            'registered' => 'registered',
            'confirmed' => 'confirmed',
            'checked in', 'checked_in' => 'checked_in',
            'attended' => 'attended',
            'cancelled', 'canceled' => 'cancelled',
            default => 'registered',
        };
    }

    protected function participantExists(Event $event, array $payload): bool
    {
        return $event->participants()
            ->where('category', $payload['category'])
            ->where(function ($query) use ($payload) {
                $query->where('name', $payload['name']);
                if (! empty($payload['surname'])) {
                    $query->where('surname', $payload['surname']);
                }

                if (! empty($payload['email'])) {
                    $query->orWhere('email', $payload['email']);
                }
            })
            ->exists();
    }

    protected function normalizeImportValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return trim($value, " \t\n\r\0\x0B`'");
    }

    protected function toNullableInt(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected function columnIndexFromReference(string $reference): int
    {
        if (! preg_match('/^[A-Z]+/', strtoupper($reference), $matches)) {
            return 0;
        }

        $letters = $matches[0];
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    protected function isEmptyImportRow(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function participantSummary(Event $event): array
    {
        $participants = $event->participants;
        $categoryCounts = collect(array_keys($this->participantCategoryLabels))
            ->mapWithKeys(fn ($category) => [$category => $participants->where('category', $category)->count()])
            ->all();

        return [
            'participant_count' => $participants->count(),
            'speaker_count' => $participants->whereIn('category', ['speaker', 'facilitator'])->count(),
            'attendee_count' => $participants->whereNotIn('category', ['speaker', 'facilitator'])->count(),
            'checked_in_count' => $participants->where('attendance_status', 'checked_in')->count(),
            'category_counts' => $categoryCounts,
        ];
    }

    protected function attendanceTrackedParticipants(Event $event): Collection
    {
        return $event->participants->filter(fn (EventParticipant $participant) => $participant->category !== 'partner');
    }

    public function participantRegisters(Event $event): array
    {
        $participants = $event->participants;

        return collect($this->participantCategoryLabels)
            ->map(function ($label, $key) use ($participants) {
                $items = $participants
                    ->where('category', $key)
                    ->values()
                    ->map(fn (EventParticipant $participant) => [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'surname' => $participant->surname,
                        'title' => $participant->title,
                        'organization_name' => $participant->organization_name,
                        'role' => $participant->role,
                        'attendance_type' => $participant->attendance_type,
                        'topic' => $participant->topic,
                        'email' => $participant->email,
                        'phone' => $participant->phone,
                        'attendance_status' => $participant->attendance_status,
                        'checked_in_at' => $participant->checked_in_at?->toDateTimeString(),
                        'notes' => $participant->notes,
                    ]);

                return [
                    'key' => $key,
                    'label' => $label,
                    'count' => $items->count(),
                    'checked_in' => $items->where('attendance_status', 'checked_in')->count(),
                    'attended' => $items->where('attendance_status', 'attended')->count(),
                    'items' => $items->all(),
                ];
            })
            ->values()
            ->all();
    }

    public function eventDaySummary(Event $event): array
    {
        $participants = $this->attendanceTrackedParticipants($event);
        $eventDayTasks = $event->workstreams->flatMap->tasks->where('phase', 'event_day');
        $postEventTasks = $event->workstreams->flatMap->tasks->where('phase', 'post_event');

        $checkedIn = $participants->where('attendance_status', 'checked_in')->count();
        $attended = $participants->where('attendance_status', 'attended')->count();
        $confirmed = $participants->where('attendance_status', 'confirmed')->count();
        $registered = $participants->where('attendance_status', 'registered')->count();

        return [
            'total_register' => $participants->count(),
            'confirmed' => $confirmed,
            'checked_in' => $checkedIn,
            'attended' => $attended,
            'outstanding_arrivals' => max(0, $confirmed + $registered - $checkedIn - $attended),
            'event_day_tasks_total' => $eventDayTasks->count(),
            'event_day_tasks_completed' => $eventDayTasks->where('status', 'completed')->count(),
            'event_day_tasks_open' => $eventDayTasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'post_event_tasks_total' => $postEventTasks->count(),
            'post_event_tasks_completed' => $postEventTasks->where('status', 'completed')->count(),
        ];
    }

    protected function outcomeReportPayload(Event $event): array
    {
        $report = $event->outcomeReport;

        return [
            'id' => $report?->id,
            'summary' => $report?->summary,
            'highlights' => $report?->highlights,
            'opportunities_created' => $report?->opportunities_created,
            'partnerships_formed' => $report?->partnerships_formed,
            'training_opportunities' => $report?->training_opportunities,
            'media_coverage' => $report?->media_coverage,
            'statistics_summary' => $report?->statistics_summary,
            'thank_you_status' => $report?->thank_you_status,
            'follow_up_actions' => $report?->follow_up_actions,
            'report_status' => $report?->report_status ?? 'draft',
            'reporter_name' => $report?->reporter
                ? trim($report->reporter->first_name.' '.$report->reporter->last_name)
                : null,
            'reported_at' => $report?->reported_at?->toDateTimeString(),
        ];
    }

    protected function planningSummary(Event $event): array
    {
        $tasks = $event->workstreams->flatMap->tasks;
        $today = now()->startOfDay();

        $statusCounts = [
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'on_going' => $tasks->where('status', 'on_going')->count(),
            'blocked' => $tasks->where('status', 'blocked')->count(),
            'cancelled' => $tasks->where('status', 'cancelled')->count(),
        ];

        $phaseCounts = collect($this->phaseLabels)->map(function ($label, $key) use ($tasks) {
            $phaseTasks = $tasks->where('phase', $key);

            return [
                'key' => $key,
                'label' => $label,
                'total' => $phaseTasks->count(),
                'completed' => $phaseTasks->where('status', 'completed')->count(),
                'open' => $phaseTasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ];
        })->values();

        $departmentSummaries = $event->workstreams
            ->sortBy('sort_order')
            ->values()
            ->map(function (EventWorkstream $workstream) use ($today) {
                $departmentTasks = $workstream->tasks;
                $activeTasks = $departmentTasks->whereNotIn('status', ['cancelled']);
                $completed = $activeTasks->where('status', 'completed')->count();
                $completionPercentage = $activeTasks->count() > 0
                    ? (int) round(($completed / $activeTasks->count()) * 100)
                    : 0;

                $groupSummaries = collect($this->taskGroupsForDepartment($workstream->name))
                    ->map(function (string $group) use ($departmentTasks) {
                        $groupTasks = $departmentTasks->where('task_group', $group)->values();
                        $activeGroupTasks = $groupTasks->whereNotIn('status', ['cancelled']);
                        $completed = $activeGroupTasks->where('status', 'completed')->count();

                        return [
                            'name' => $group,
                            'total' => $groupTasks->count(),
                            'completed' => $completed,
                            'open' => $groupTasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
                            'completion_percentage' => $activeGroupTasks->count() > 0
                                ? (int) round(($completed / $activeGroupTasks->count()) * 100)
                                : 0,
                        ];
                    })
                    ->filter(fn (array $group) => $group['total'] > 0)
                    ->values();

                return [
                    'id' => $workstream->id,
                    'name' => $workstream->name,
                    'total' => $departmentTasks->count(),
                    'completed' => $departmentTasks->where('status', 'completed')->count(),
                    'open' => $departmentTasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
                    'overdue' => $departmentTasks
                        ->filter(fn (EventTask $task) => $task->due_date && $task->due_date->lt($today) && ! in_array($task->status, ['completed', 'cancelled'], true))
                        ->count(),
                    'completion_percentage' => $completionPercentage,
                    'task_groups' => $groupSummaries,
                ];
            });

        $activeTasks = $tasks->whereNotIn('status', ['cancelled']);
        $overallCompletionPercentage = $activeTasks->count() > 0
            ? (int) round(($activeTasks->where('status', 'completed')->count() / $activeTasks->count()) * 100)
            : 0;

        return [
            'total_workstreams' => $event->workstreams->count(),
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $statusCounts['completed'],
            'open_tasks' => $tasks->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completion_percentage' => $overallCompletionPercentage,
            'overdue_tasks' => $tasks
                ->filter(fn (EventTask $task) => $task->due_date && $task->due_date->lt($today) && ! in_array($task->status, ['completed', 'cancelled'], true))
                ->count(),
            'status_counts' => $statusCounts,
            'phase_counts' => $phaseCounts,
            'department_summaries' => $departmentSummaries->all(),
        ];
    }

    protected function taskGroupsForDepartment(string $department): array
    {
        $normalized = match ($department) {
            'Marketing and Communications' => 'Marketing',
            'Media and Technical' => 'Technical',
            'Impact and Reporting' => 'Impact & Reporting',
            default => $department,
        };

        return $this->departmentTaskGroups[$normalized] ?? ['General'];
    }

    public function ensurePlanningTemplate(Event $event): void
    {
        if ($event->workstreams()->exists()) {
            return;
        }

        $workstreams = collect([
            [
                'name' => 'Administration',
                'description' => 'Venue readiness, logistics, compliance, and registration infrastructure.',
                'sort_order' => 1,
                'tasks' => [
                    ['phase' => 'pre_event', 'task_group' => 'Venue', 'duty' => 'Source venue quotations', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 1, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Venue', 'duty' => 'Venue and holding room booking', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 2, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Speakers', 'duty' => 'Prepare speaker coordination list', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 3, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Participants', 'duty' => 'Prepare participant coordination list', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 4, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'JOC / Compliance', 'duty' => 'JOC / VOC compliance confirmation', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 5, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'JOC / Compliance', 'duty' => 'Submit event characterisation', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 6, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'JOC / Compliance', 'duty' => 'Confirm public liability insurance', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 7, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'JOC / Compliance', 'duty' => 'Confirm EMS availability', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 8, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'JOC / Compliance', 'duty' => 'Appoint safety officer', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 9, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'JOC / Compliance', 'duty' => 'Appoint public officer', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 10, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Participants', 'duty' => 'Attendance registers for VIP, speakers, attendees, exhibitors, team, and media', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 11, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Transport / Accommodation', 'duty' => 'Coordinate transport requirements', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 12, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Transport / Accommodation', 'duty' => 'Coordinate accommodation requirements', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 13, 'is_custom' => false],
                    ['phase' => 'event_day', 'task_group' => 'General Logistics', 'duty' => 'Manage registration desk and event coordination', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 14, 'is_custom' => false],
                    ['phase' => 'post_event', 'task_group' => 'General Logistics', 'duty' => 'Consolidate participation statistics and attendance packs', 'responsible_person' => 'Administration team', 'status' => 'pending', 'sort_order' => 15, 'is_custom' => false],
                ],
            ],
            [
                'name' => 'Marketing',
                'description' => 'Promotion, stakeholder communications, and event publicity.',
                'sort_order' => 2,
                'tasks' => [
                    ['phase' => 'pre_event', 'task_group' => 'Content & Communications', 'duty' => 'Event marketing strategy and content rollout plan', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 1, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Outreach & Stakeholder Communication', 'duty' => 'Identify and write to speakers', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 2, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Graphic Design', 'duty' => 'Design main poster', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 3, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Graphic Design', 'duty' => 'Prepare email signatures', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 4, 'is_custom' => false],
                    ['phase' => 'pre_event', 'task_group' => 'Graphic Design', 'duty' => 'Prepare concept documents and slide assets', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 5, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Outreach & Stakeholder Communication', 'duty' => 'Identify and brief media partners plus stakeholder call-outs', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 6, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Content & Communications', 'duty' => 'Develop event write-ups and content plan', 'responsible_person' => 'Marketing team', 'status' => 'pending', 'sort_order' => 7, 'is_custom' => false],
                    ['phase' => 'event_day', 'task_group' => 'Content & Communications', 'duty' => 'Manage social updates, interviews, and stakeholder communication', 'responsible_person' => 'Communications team', 'status' => 'pending', 'sort_order' => 8, 'is_custom' => false],
                    ['phase' => 'post_event', 'task_group' => 'Outreach & Stakeholder Communication', 'duty' => 'Send thank-you messages to speakers, sponsors, partners, and exhibitors', 'responsible_person' => 'Communications team', 'status' => 'pending', 'sort_order' => 9, 'is_custom' => false],
                ],
            ],
            [
                'name' => 'Technical',
                'description' => 'Streaming, AV support, presentations, and technical operations.',
                'sort_order' => 3,
                'tasks' => [
                    ['phase' => 'preparations', 'task_group' => 'AV / Equipment', 'duty' => 'Obtain live streaming equipment quotation', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 1, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Streaming & Virtual Access', 'duty' => 'Run livestream dry run', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 2, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Streaming & Virtual Access', 'duty' => 'Prepare Zoom links for speakers and attendees', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 3, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'AV / Equipment', 'duty' => 'Confirm livestream, microphone, and presentation requirements', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 4, 'is_custom' => false],
                    ['phase' => 'preparations', 'task_group' => 'Registration Systems', 'duty' => 'Confirm website registrations and event form pipeline', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 5, 'is_custom' => false],
                    ['phase' => 'event_day', 'task_group' => 'Presentation Support', 'duty' => 'Control presentation slides and stage AV', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 6, 'is_custom' => false],
                    ['phase' => 'event_day', 'task_group' => 'Streaming & Virtual Access', 'duty' => 'Manage live streaming and virtual access', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 7, 'is_custom' => false],
                    ['phase' => 'event_day', 'task_group' => 'Media Capture', 'duty' => 'Coordinate photography and videography', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 8, 'is_custom' => false],
                    ['phase' => 'post_event', 'task_group' => 'Media Capture', 'duty' => 'Archive media assets and uploads', 'responsible_person' => 'Technical team', 'status' => 'pending', 'sort_order' => 9, 'is_custom' => false],
                ],
            ],
            [
                'name' => 'Impact & Reporting',
                'description' => 'Post-event reporting, outcomes, and opportunity capture.',
                'sort_order' => 4,
                'tasks' => [
                    ['phase' => 'preparations', 'task_group' => 'Impact Measurement', 'duty' => 'Prepare post-event reporting framework and metrics', 'responsible_person' => 'Reporting team', 'status' => 'pending', 'sort_order' => 1, 'is_custom' => false],
                    ['phase' => 'post_event', 'task_group' => 'Reporting', 'duty' => 'Develop post-event report and document highlights', 'responsible_person' => 'Reporting team', 'status' => 'pending', 'sort_order' => 2, 'is_custom' => false],
                    ['phase' => 'post_event', 'task_group' => 'Opportunities & Follow-up', 'duty' => 'Capture opportunities created including training and partnerships formed', 'responsible_person' => 'Reporting team', 'status' => 'pending', 'sort_order' => 3, 'is_custom' => false],
                ],
            ],
        ]);

        DB::transaction(function () use ($event, $workstreams) {
            foreach ($workstreams as $workstreamData) {
                $workstream = $event->workstreams()->create([
                    'name' => $workstreamData['name'],
                    'description' => $workstreamData['description'],
                    'sort_order' => $workstreamData['sort_order'],
                ]);

                foreach ($workstreamData['tasks'] as $taskData) {
                    $workstream->tasks()->create($taskData);
                }
            }
        });
    }

    protected function syncTaskEvidence(Event $event, EventTask $task, array $data, ?UploadedFile $evidenceFile = null): void
    {
        if (($data['remove_evidence_file'] ?? false) && $task->evidence_path && $task->evidence_disk) {
            Storage::disk($task->evidence_disk)->delete($task->evidence_path);
            $task->forceFill([
                'evidence_disk' => null,
                'evidence_path' => null,
                'evidence_file_name' => null,
                'evidence_mime_type' => null,
                'evidence_file_size' => null,
            ])->save();
        }

        if (! $evidenceFile) {
            return;
        }

        if ($task->evidence_path && $task->evidence_disk) {
            Storage::disk($task->evidence_disk)->delete($task->evidence_path);
        }

        $path = $evidenceFile->store("event-task-evidence/{$event->id}", 'local');

        $task->forceFill([
            'evidence_disk' => 'local',
            'evidence_path' => $path,
            'evidence_file_name' => $evidenceFile->getClientOriginalName(),
            'evidence_mime_type' => $evidenceFile->getClientMimeType(),
            'evidence_file_size' => $evidenceFile->getSize(),
        ])->save();
    }
}
