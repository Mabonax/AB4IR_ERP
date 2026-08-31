<?php

namespace App\Domains\Events\Controllers;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventClosureAsset;
use App\Domains\Events\Requests\CompleteEventRequest;
use App\Domains\Events\Requests\EventClosureAssetUploadRequest;
use App\Domains\Events\Requests\EventLifecycleActionRequest;
use App\Domains\Events\Models\EventWorkstream;
use App\Domains\Events\Services\EventService;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function __construct(
        protected EventService $service
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Event::class);

        $events = $this->service->paginateEvents();
        $stats = $this->service->dashboardStats($events);

        return Inertia::render('Events/Index', [
            'events' => $events->through(fn (Event $event) => $this->service->mapEvent($event)),
            'stats' => $stats,
            'staffMembers' => StaffMember::query()
                ->select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (StaffMember $staff) => [
                    'id' => $staff->id,
                    'name' => trim($staff->first_name.' '.$staff->last_name),
                ])->values(),
            'stakeholders' => Stakeholder::query()
                ->select('id', 'organization_name', 'name')
                ->orderBy('organization_name')
                ->get()
                ->map(fn (Stakeholder $stakeholder) => [
                    'id' => $stakeholder->id,
                    'name' => trim(($stakeholder->organization_name ?: 'Stakeholder').' - '.$stakeholder->name),
                ])->values(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Event::class);

        return Inertia::render('Events/Create', [
            'staffMembers' => $this->staffMembers(),
            'stakeholders' => $this->stakeholders(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $data = $this->validateEvent($request);
        $event = $this->service->createEvent($data);

        return redirect()->route('events.show', $event->id)->with('success', 'Event created.');
    }

    public function show(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        return Inertia::render('Events/Show', [
            'event' => $this->service->mapEvent($model),
            'staffMembers' => $this->staffMembers(),
            'stakeholders' => $this->stakeholders(),
        ]);
    }

    public function series(string $seriesKey)
    {
        $this->authorize('viewAny', Event::class);

        return Inertia::render('Events/Series', [
            'series' => $this->service->seriesOverview($seriesKey),
        ]);
    }

    public function edit(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        return Inertia::render('Events/Edit', [
            'event' => $this->service->mapEvent($model),
            'staffMembers' => $this->staffMembers(),
            'stakeholders' => $this->stakeholders(),
        ]);
    }

    public function participants(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        return Inertia::render('Events/Participants', [
            'event' => $this->service->mapEvent($model),
        ]);
    }

    public function registersPage(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        return Inertia::render('Events/Registers', [
            'event' => $this->service->mapEvent($model),
        ]);
    }

    public function eventDay(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        return Inertia::render('Events/EventDay', [
            'event' => $this->service->mapEvent($model),
        ]);
    }

    public function createWorkstreamPage(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        return Inertia::render('Events/Workstreams/Create', [
            'event' => $this->service->mapEvent($model),
        ]);
    }

    public function editWorkstreamPage(int $event, int $workstream)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $workstreamModel = $model->workstreams()->findOrFail($workstream);

        return Inertia::render('Events/Workstreams/Edit', [
            'event' => $this->service->mapEvent($model),
            'workstream' => [
                'id' => $workstreamModel->id,
                'name' => $workstreamModel->name,
                'description' => $workstreamModel->description,
                'sort_order' => $workstreamModel->sort_order,
            ],
        ]);
    }

    public function createTaskPage(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $eventData = $this->service->mapEvent($model);
        $defaultWorkstreamId = (int) $request->integer('event_workstream_id');
        $defaultPhase = $request->string('phase')->toString();

        return Inertia::render('Events/Tasks/Create', [
            'event' => $eventData,
            'defaults' => [
                'event_workstream_id' => $defaultWorkstreamId ?: ($eventData['workstreams'][0]['id'] ?? null),
                'phase' => in_array($defaultPhase, ['pre_event', 'preparations', 'event_day', 'post_event'], true) ? $defaultPhase : 'pre_event',
            ],
        ]);
    }

    public function editTaskPage(int $event, int $task)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $taskModel = $model->workstreams()
            ->with('tasks')
            ->get()
            ->flatMap->tasks
            ->firstWhere('id', $task);

        abort_if(! $taskModel, 404);

        return Inertia::render('Events/Tasks/Edit', [
            'event' => $this->service->mapEvent($model),
            'task' => [
                'id' => $taskModel->id,
                'event_workstream_id' => $taskModel->event_workstream_id,
                'phase' => $taskModel->phase,
                'task_group' => $taskModel->task_group,
                'is_custom' => $taskModel->is_custom,
                'duty' => $taskModel->duty,
                'due_date' => $taskModel->due_date?->format('Y-m-d'),
                'responsible_person' => $taskModel->responsible_person,
                'outcome' => $taskModel->outcome,
                'status' => $taskModel->status,
                'comment' => $taskModel->comment,
                'evidence_url' => $taskModel->evidence_url,
                'evidence_file_name' => $taskModel->evidence_file_name,
                'has_evidence_file' => filled($taskModel->evidence_path),
                'sort_order' => $taskModel->sort_order,
            ],
        ]);
    }

    public function reportPdf(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        $pdf = Pdf::loadView('pdf.event-report', $this->service->eventReportPayload($model))
            ->setPaper('a4', 'portrait');

        return $pdf->download('event-report-'.$model->id.'.pdf');
    }

    public function registerPdf(int $event, ?string $category = null)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        $registers = collect($this->service->participantRegisters($model));
        $selected = $category ? $registers->firstWhere('key', $category) : null;

        abort_if($category && ! $selected, 404);

        $pdf = Pdf::loadView('pdf.event-register', [
            'event' => $model,
            'registers' => $selected ? [$selected] : $registers->all(),
            'category' => $category,
            'eventDaySummary' => $this->service->eventDaySummary($model),
        ])->setPaper('a4', 'portrait');

        $suffix = $category ? '-'.$category : '-all';

        return $pdf->download('event-register-'.$model->id.$suffix.'.pdf');
    }

    public function registerCsv(int $event, ?string $category = null): StreamedResponse
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        $registers = collect($this->service->participantRegisters($model));
        $selected = $category ? $registers->firstWhere('key', $category) : null;

        abort_if($category && ! $selected, 404);

        $rows = $selected ? [$selected] : $registers->all();
        $suffix = $category ? '-'.$category : '-all';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Category', 'Name', 'Title', 'Organization', 'Role', 'Attendance Type', 'Topic', 'Email', 'Phone', 'Attendance Status', 'Checked In At', 'Notes']);
            foreach ($rows as $register) {
                foreach ($register['items'] as $item) {
                    fputcsv($handle, [
                        $register['label'],
                        $item['name'],
                        $item['title'],
                        $item['organization_name'],
                        $item['role'],
                        $item['attendance_type'],
                        $item['topic'],
                        $item['email'],
                        $item['phone'],
                        $item['attendance_status'],
                        $item['checked_in_at'],
                        $item['notes'],
                    ]);
                }
            }
            fclose($handle);
        }, 'event-register-'.$model->id.$suffix.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function update(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $this->validateEvent($request);
        $updated = $this->service->updateEvent($event, $data);

        return redirect()->route('events.show', $updated->id)->with('success', 'Event updated.');
    }

    public function destroy(int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('delete', $model);
        $this->service->deleteEvent($event);

        return redirect()->route('events.index')->with('success', 'Event deleted.');
    }

    public function openRegistration(EventLifecycleActionRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->openRegistration($model, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Registration opened.');
    }

    public function closeRegistration(EventLifecycleActionRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->closeRegistration($model, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Registration closed.');
    }

    public function startLifecycle(EventLifecycleActionRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->startEvent($model, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Event started.');
    }

    public function complete(CompleteEventRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->completeEvent($model, $request->user(), $request->validated());

        return redirect()->back()->with('success', 'Event completed and closure report recorded.');
    }

    public function cancelLifecycle(EventLifecycleActionRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->cancelEvent($model, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Event cancelled.');
    }

    public function postpone(EventLifecycleActionRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->postponeEvent($model, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Event postponed.');
    }

    public function archive(EventLifecycleActionRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->archiveEvent($model, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Event archived.');
    }

    public function uploadClosureAsset(EventClosureAssetUploadRequest $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('manageLifecycle', $model);
        $this->service->uploadClosureAsset(
            $model,
            $request->user(),
            $request->file('file'),
            $request->string('category')->toString(),
            $request->string('description')->toString() ?: null,
        );

        return redirect()->back()->with('success', 'Closure asset uploaded.');
    }

    public function downloadClosureAsset(int $event, int $asset)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        return $this->service->downloadClosureAsset($model, EventClosureAsset::query()->findOrFail($asset));
    }

    public function storeSpeaker(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'topic' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:4000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:1',
        ]);

        $this->service->addSpeaker($model, $data);

        return redirect()->back()->with('success', 'Speaker added.');
    }

    public function destroySpeaker(int $event, int $speaker)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);
        $this->service->removeSpeaker($model, $speaker);

        return redirect()->back()->with('success', 'Speaker removed.');
    }

    public function storeAttendee(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'organization_name' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'attendance_type' => 'required|string|max:255',
        ]);

        $this->service->addAttendee($model, $data);

        return redirect()->back()->with('success', 'Attendee added.');
    }

    public function updateAttendeeStatus(Request $request, int $event, int $attendee)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'attendance_status' => 'required|in:registered,confirmed,checked_in,attended,cancelled',
        ]);

        $this->service->updateAttendeeStatus($model, $attendee, $data['attendance_status']);

        return redirect()->back()->with('success', 'Attendee status updated.');
    }

    public function destroyAttendee(int $event, int $attendee)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);
        $this->service->removeAttendee($model, $attendee);

        return redirect()->back()->with('success', 'Attendee removed.');
    }

    public function storeParticipant(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $this->validateParticipant($request);
        $this->service->addParticipant($model, $data);

        return redirect()->back()->with('success', 'Participant added.');
    }

    public function updateParticipant(Request $request, int $event, int $participant)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $this->validateParticipant($request);
        $this->service->updateParticipant($model, $participant, $data);

        return redirect()->back()->with('success', 'Participant updated.');
    }

    public function updateParticipantStatus(Request $request, int $event, int $participant)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'attendance_status' => 'required|in:registered,confirmed,checked_in,attended,cancelled',
        ]);

        $this->service->updateParticipantStatus($model, $participant, $data['attendance_status']);

        return redirect()->back()->with('success', 'Participant status updated.');
    }

    public function destroyParticipant(int $event, int $participant)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);
        $this->service->removeParticipant($model, $participant);

        return redirect()->back()->with('success', 'Participant removed.');
    }

    public function importParticipants(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:20480',
            'category_context' => 'nullable|in:speaker,facilitator,attendee,exhibitor,sponsor,partner,media_house,vip,team_board',
        ]);

        $summary = $this->service->importParticipantsFromFile($model, $data['file'], $data['category_context'] ?? null);

        return redirect()->back()->with([
            'success' => "Participant import completed. Processed {$summary['processed']} rows, created {$summary['created']}, duplicates {$summary['duplicates']}.",
            'import_errors' => $summary['errors'],
        ]);
    }

    public function upsertOutcomeReport(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'summary' => 'nullable|string|max:12000',
            'highlights' => 'nullable|string|max:12000',
            'opportunities_created' => 'nullable|string|max:12000',
            'partnerships_formed' => 'nullable|string|max:12000',
            'training_opportunities' => 'nullable|string|max:12000',
            'media_coverage' => 'nullable|string|max:12000',
            'statistics_summary' => 'nullable|string|max:12000',
            'thank_you_status' => 'nullable|string|max:4000',
            'follow_up_actions' => 'nullable|string|max:12000',
            'report_status' => 'required|in:draft,submitted,finalized',
        ]);

        $this->service->upsertOutcomeReport($model, $data);

        return redirect()->back()->with('success', 'Post-event report updated.');
    }

    public function storeWorkstream(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:4000',
            'sort_order' => 'nullable|integer|min:1|max:1000',
        ]);

        $this->service->addWorkstream($model, $data);

        return redirect()->back()->with('success', 'Workstream added.');
    }

    public function updateWorkstream(Request $request, int $event, int $workstream)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:4000',
            'sort_order' => 'nullable|integer|min:1|max:1000',
        ]);

        $this->service->updateWorkstream($model, $workstream, $data);

        return redirect()->back()->with('success', 'Workstream updated.');
    }

    public function destroyWorkstream(int $event, int $workstream)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);
        $this->service->removeWorkstream($model, $workstream);

        return redirect()->back()->with('success', 'Workstream removed.');
    }

    public function storeTask(Request $request, int $event)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $this->validateTask($request);
        $this->service->addTask($model, $data, $request->file('evidence_file'));

        return redirect()->back()->with('success', 'Event task added.');
    }

    public function updateTask(Request $request, int $event, int $task)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);

        $data = $this->validateTask($request);
        $this->service->updateTask($model, $task, $data, $request->file('evidence_file'));

        return redirect()->back()->with('success', 'Event task updated.');
    }

    public function destroyTask(int $event, int $task)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('update', $model);
        $this->service->removeTask($model, $task);

        return redirect()->back()->with('success', 'Event task removed.');
    }

    public function downloadTaskEvidence(int $event, int $task)
    {
        $model = $this->service->getEvent($event);
        $this->authorize('view', $model);

        return $this->service->downloadTaskEvidence($model, $task);
    }

    protected function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'nullable|string|max:255',
            'event_format' => 'nullable|in:physical,virtual,hybrid',
            'annual_series_key' => 'nullable|string|max:255',
            'event_year' => 'nullable|integer|min:2000|max:2100',
            'is_annual' => 'nullable|boolean',
            'theme' => 'nullable|string|max:255',
            'track_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string|max:4000',
            'venue_contact_person' => 'nullable|string|max:255',
            'venue_contact_phone' => 'nullable|string|max:50',
            'venue_contact_email' => 'nullable|email|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planned,open_for_registration,registration_closed,active,completed,cancelled,postponed,archived',
            'description' => 'nullable|string|max:4000',
            'objectives' => 'nullable|string|max:4000',
            'technical_requirements' => 'nullable|string|max:4000',
            'registration_link' => 'nullable|url|max:255',
            'zoom_join_url' => 'nullable|url|max:255',
            'zoom_host_url' => 'nullable|url|max:255',
            'zoom_meeting_id' => 'nullable|string|max:255',
            'zoom_passcode' => 'nullable|string|max:255',
            'expected_attendees' => 'nullable|integer|min:0|max:100000',
            'owner_staff_member_id' => 'nullable|exists:staff_members,id',
            'partner_stakeholder_ids' => 'nullable|array',
            'partner_stakeholder_ids.*' => 'integer|exists:stakeholders,id',
        ]);
    }

    protected function validateTask(Request $request): array
    {
        $validated = $request->validate([
            'event_workstream_id' => 'required|integer|exists:event_workstreams,id',
            'phase' => 'required|in:pre_event,preparations,event_day,post_event',
            'task_group' => 'nullable|string|max:255',
            'duty' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'responsible_person' => 'nullable|string|max:255',
            'outcome' => 'nullable|string|max:255',
            'status' => 'required|in:pending,in_progress,completed,on_going,blocked,cancelled',
            'comment' => 'nullable|string|max:4000',
            'evidence_url' => 'nullable|url|max:2048',
            'evidence_file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,webp',
            'remove_evidence_file' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:1|max:1000',
            'is_custom' => 'nullable|boolean',
        ]);

        if (! filled($validated['task_group'] ?? null)) {
            $validated['task_group'] = EventWorkstream::query()
                ->whereKey($validated['event_workstream_id'])
                ->value('name') === 'Administration'
                ? 'General Logistics'
                : 'General';
        }

        return $validated;
    }

    protected function validateParticipant(Request $request): array
    {
        return $request->validate([
            'category' => 'required|in:speaker,facilitator,attendee,exhibitor,sponsor,partner,media_house,vip,team_board',
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'topic' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:4000',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'nullable|string|max:255',
            'attendance_type' => 'required_if:category,attendee|string|max:255',
            'attendance_status' => 'nullable|in:registered,confirmed,checked_in,attended,cancelled',
            'notes' => 'nullable|string|max:4000',
            'sort_order' => 'nullable|integer|min:1|max:1000',
        ]);
    }

    protected function staffMembers()
    {
        return StaffMember::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (StaffMember $staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
            ])->values();
    }

    protected function stakeholders()
    {
        return Stakeholder::query()
            ->select('id', 'organization_name', 'name')
            ->orderBy('organization_name')
            ->get()
            ->map(fn (Stakeholder $stakeholder) => [
                'id' => $stakeholder->id,
                'name' => trim(($stakeholder->organization_name ?: 'Stakeholder').' - '.$stakeholder->name),
            ])->values();
    }
}
