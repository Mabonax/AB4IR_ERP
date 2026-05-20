<?php

use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventTask;
use App\Domains\Events\Models\EventWorkstream;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeEventOwnerGraph(): array
{
    $user = User::factory()->create();

    $department = StaffDepartment::query()->create([
        'name' => 'Events Department '.Str::upper(Str::random(4)),
        'description' => 'Events Department',
    ]);

    $staff = StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Eva',
        'last_name' => 'Owner',
        'email' => 'event-owner-'.Str::lower(Str::random(6)).'@example.com',
        'employee_number' => 'EMP-EVT-'.Str::upper(Str::random(6)),
        'status' => 'active',
    ]);

    return compact('user', 'staff');
}

function makeOrganizationManager(): User
{
    $user = grantDomainAccess(User::factory()->create(), 'organization');

    $department = StaffDepartment::query()->create([
        'name' => 'Organization Department '.Str::upper(Str::random(4)),
        'description' => 'Organization Department',
    ]);

    StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Olivia',
        'last_name' => 'Manager',
        'email' => 'organization-manager-'.Str::lower(Str::random(6)).'@example.com',
        'employee_number' => 'EMP-ORG-'.Str::upper(Str::random(6)),
        'status' => 'active',
        'is_manager' => true,
    ]);

    return $user;
}

test('organization profile can be updated and viewed as a shared institutional source', function () {
    $user = makeOrganizationManager();

    $response = $this->actingAs($user)->put(route('organization.update'), [
        'name' => 'AB4IR Enterprise Development Centre',
        'legal_name' => 'AB4IR NPC',
        'tagline' => 'Catalysing incubation and enterprise growth',
        'mission' => 'To incubate viable enterprises and strengthen entrepreneurs.',
        'vision' => 'A thriving and inclusive business ecosystem.',
        'about' => 'AB4IR supports incubation, enterprise support, and institutional development.',
        'core_values' => 'Innovation, Inclusion, Accountability',
        'service_offering' => 'Incubation, acceleration, training, and ecosystem support.',
        'website' => 'https://ab4ir.example.com',
        'email' => 'info@ab4ir.example.com',
        'phone' => '+27 11 000 0000',
        'address_line_1' => '1 Innovation Way',
        'city' => 'Johannesburg',
        'province' => 'Gauteng',
        'country' => 'South Africa',
        'postal_code' => '2000',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Organization profile updated.');

    $this->assertDatabaseHas('organization_profiles', [
        'name' => 'AB4IR Enterprise Development Centre',
        'legal_name' => 'AB4IR NPC',
    ]);

    $show = $this->actingAs($user)->get(route('organization.show'));
    $show->assertOk();
    $show->assertSee('AB4IR Enterprise Development Centre');
    $show->assertSee('Catalysing incubation and enterprise growth');
});

test('organization logos can be uploaded for multiple approved brand versions', function () {
    Storage::fake('public');

    $user = makeOrganizationManager();

    $response = $this->actingAs($user)->post(route('organization.logos.update'), [
        'primary_logo' => UploadedFile::fake()->image('primary-logo.png'),
        'light_logo' => UploadedFile::fake()->image('light-logo.png'),
        'dark_logo' => UploadedFile::fake()->image('dark-logo.png'),
        'icon_logo' => UploadedFile::fake()->image('icon-logo.png'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Organization logos updated.');

    $profile = OrganizationProfile::query()->firstOrFail();

    expect($profile->primary_logo_path)->not->toBeNull();
    expect($profile->light_logo_path)->not->toBeNull();
    expect($profile->dark_logo_path)->not->toBeNull();
    expect($profile->icon_logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($profile->primary_logo_path);
    Storage::disk('public')->assertExists($profile->light_logo_path);
    Storage::disk('public')->assertExists($profile->dark_logo_path);
    Storage::disk('public')->assertExists($profile->icon_logo_path);
});

test('event managers can create annual events and manage broader event participants', function () {
    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');
    $partner = Stakeholder::query()->create([
        'organization_name' => 'Innovation Forum',
        'name' => 'Partnership Desk',
        'email' => 'partners@example.com',
        'contact_number' => '0711111111',
        'status' => 'active',
    ]);

    $createPage = $this->actingAs($graph['user'])->get(route('events.create'));
    $createPage->assertOk();

    $create = $this->actingAs($graph['user'])->post(route('events.store'), [
        'title' => 'Annual Incubation Summit',
        'event_type' => 'Summit',
        'event_format' => 'hybrid',
        'annual_series_key' => 'incubation-summit',
        'event_year' => 2026,
        'is_annual' => true,
        'theme' => 'Growing Incubation Pathways',
        'track_name' => 'Ecosystem Development',
        'location' => 'Johannesburg',
        'venue_name' => 'Innovation Centre',
        'venue_address' => '1 Innovation Way, Johannesburg',
        'venue_contact_person' => 'Venue Desk',
        'venue_contact_phone' => '0719999999',
        'venue_contact_email' => 'venue@example.com',
        'start_date' => '2026-09-15',
        'end_date' => '2026-09-16',
        'status' => 'planned',
        'description' => 'A flagship annual event for incubatees and partners.',
        'objectives' => 'Showcase incubatees and connect the ecosystem.',
        'technical_requirements' => 'Projector, microphones, livestream desk',
        'registration_link' => 'https://events.example.com/summit',
        'zoom_join_url' => 'https://zoom.example.com/join/summit',
        'zoom_host_url' => 'https://zoom.example.com/host/summit',
        'zoom_meeting_id' => '123 456 7890',
        'zoom_passcode' => 'SUMMIT2026',
        'expected_attendees' => 150,
        'owner_staff_member_id' => $graph['staff']->id,
        'partner_stakeholder_ids' => [$partner->id],
    ]);

    $create->assertRedirect(route('events.show', 1));
    $create->assertSessionHas('success', 'Event created.');

    $event = Event::query()->firstOrFail();

    $editPage = $this->actingAs($graph['user'])->get(route('events.edit', $event->id));
    $editPage->assertOk();

    $speaker = $this->actingAs($graph['user'])->post(route('events.participants.store', $event->id), [
        'category' => 'speaker',
        'name' => 'Dr Jane Speaker',
        'title' => 'CEO',
        'organization_name' => 'Innovation Forum',
        'topic' => 'Scaling incubation ecosystems',
        'bio' => 'Experienced innovation leader.',
        'email' => 'speaker@example.com',
        'phone' => '0712345678',
        'attendance_status' => 'confirmed',
        'sort_order' => 1,
    ]);

    $speaker->assertRedirect();
    $speaker->assertSessionHas('success', 'Participant added.');

    $attendee = $this->actingAs($graph['user'])->post(route('events.participants.store', $event->id), [
        'category' => 'attendee',
        'name' => 'John Attendee',
        'email' => 'attendee@example.com',
        'phone' => '0722222222',
        'organization_name' => 'AB4IR',
        'role' => 'Guest',
        'attendance_type' => 'In-person',
    ]);

    $attendee->assertRedirect();
    $attendee->assertSessionHas('success', 'Participant added.');

    $vip = $this->actingAs($graph['user'])->post(route('events.participants.store', $event->id), [
        'category' => 'vip',
        'name' => 'Executive Guest',
        'organization_name' => 'Department of Trade',
        'role' => 'VIP Guest',
        'attendance_status' => 'confirmed',
    ]);

    $vip->assertRedirect();
    $vip->assertSessionHas('success', 'Participant added.');

    $attendeeId = $event->participants()->where('category', 'attendee')->firstOrFail()->id;

    $checkIn = $this->actingAs($graph['user'])->post(route('events.participants.status', [
        'event' => $event->id,
        'participant' => $attendeeId,
    ]), [
        'attendance_status' => 'checked_in',
    ]);

    $checkIn->assertRedirect();
    $checkIn->assertSessionHas('success', 'Participant status updated.');

    $show = $this->actingAs($graph['user'])->get(route('events.show', $event->id));
    $show->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.title', 'Annual Incubation Summit')
            ->where('event.annual_series_key', 'incubation-summit')
            ->where('event.partner_names.0', 'Innovation Forum - Partnership Desk')
            ->where('event.speakers.0.name', 'Dr Jane Speaker')
            ->where('event.attendees.0.name', 'John Attendee')
            ->where('event.participant_summary.category_counts.speaker', 1)
            ->where('event.participant_summary.category_counts.vip', 1)
            ->where('event.planning_summary.total_workstreams', 4)
            ->where('event.workstreams.0.name', 'Administration')
        );

    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'category' => 'speaker',
        'name' => 'Dr Jane Speaker',
    ]);
    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'category' => 'attendee',
        'name' => 'John Attendee',
        'attendance_status' => 'checked_in',
    ]);
    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'category' => 'vip',
        'name' => 'Executive Guest',
    ]);
    $this->assertDatabaseHas('event_partner_stakeholders', [
        'event_id' => $event->id,
        'stakeholder_id' => $partner->id,
    ]);
    expect($event->fresh()->workstreams()->count())->toBeGreaterThan(0);
    expect($event->fresh()->workstreams()->where('name', 'Administration')->exists())->toBeTrue();
    expect($event->fresh()->workstreams()->where('name', 'Administration')->first()?->tasks()->where('duty', 'Appoint public officer')->exists())->toBeTrue();
});

test('event managers can manage workstreams and planning tasks', function () {
    Storage::fake('local');

    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');

    $event = Event::query()->create([
        'title' => 'Planning Backbone Event',
        'event_type' => 'Conference',
        'start_date' => '2026-08-20',
        'status' => 'planned',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    app(\App\Domains\Events\Services\EventService::class)->ensurePlanningTemplate($event);

    $createWorkstream = $this->actingAs($graph['user'])->post(route('events.workstreams.store', $event->id), [
        'name' => 'Exhibitor and Partner Management',
        'description' => 'Handle sponsors, partners, and exhibitors.',
        'sort_order' => 8,
    ]);

    $createWorkstream->assertRedirect();
    $createWorkstream->assertSessionHas('success', 'Workstream added.');

    $workstream = EventWorkstream::query()
        ->where('event_id', $event->id)
        ->where('name', 'Exhibitor and Partner Management')
        ->firstOrFail();

    $createTask = $this->actingAs($graph['user'])->post(route('events.tasks.store', $event->id), [
        'event_workstream_id' => $workstream->id,
        'phase' => 'preparations',
        'duty' => 'Finalize exhibitor packs',
        'due_date' => '2026-08-15',
        'responsible_person' => 'Events team',
        'outcome' => 'Exhibitor packs ready for setup',
        'status' => 'pending',
        'comment' => 'Awaiting final branding assets',
        'sort_order' => 1,
    ]);

    $createTask->assertRedirect();
    $createTask->assertSessionHas('success', 'Event task added.');

    $task = EventTask::query()
        ->whereHas('workstream', fn ($query) => $query->where('event_id', $event->id))
        ->where('duty', 'Finalize exhibitor packs')
        ->firstOrFail();

    $updateTask = $this->actingAs($graph['user'])->put(route('events.tasks.update', [
        'event' => $event->id,
        'task' => $task->id,
    ]), [
        'event_workstream_id' => $workstream->id,
        'phase' => 'preparations',
        'task_group' => 'General',
        'duty' => 'Finalize exhibitor packs',
        'due_date' => '2026-08-16',
        'responsible_person' => 'Events operations team',
        'outcome' => 'Exhibitor packs approved',
        'status' => 'in_progress',
        'comment' => 'Branding assets received and printing started',
        'evidence_url' => 'https://example.com/quotation/live-streaming',
        'evidence_file' => UploadedFile::fake()->create('quotation.pdf', 24, 'application/pdf'),
        'sort_order' => 2,
    ]);

    $updateTask->assertRedirect();
    $updateTask->assertSessionHas('success', 'Event task updated.');

    $this->assertDatabaseHas('event_tasks', [
        'id' => $task->id,
        'status' => 'in_progress',
        'responsible_person' => 'Events operations team',
        'evidence_url' => 'https://example.com/quotation/live-streaming',
        'sort_order' => 2,
    ]);

    $task = $task->fresh();
    expect($task->evidence_path)->not->toBeNull();
    Storage::disk('local')->assertExists($task->evidence_path);

    $show = $this->actingAs($graph['user'])->get(route('events.show', $event->id));
    $show->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.workstreams.4.name', 'Exhibitor and Partner Management')
            ->where('event.workstreams.4.tasks.0.duty', 'Finalize exhibitor packs')
            ->where('event.workstreams.4.tasks.0.status', 'in_progress')
            ->where('event.workstreams.4.tasks.0.has_evidence_file', true)
        );

    $downloadEvidence = $this->actingAs($graph['user'])->get(route('events.tasks.evidence', [
        'event' => $event->id,
        'task' => $task->id,
    ]));

    $downloadEvidence->assertOk();

    $deleteTask = $this->actingAs($graph['user'])->delete(route('events.tasks.destroy', [
        'event' => $event->id,
        'task' => $task->id,
    ]));

    $deleteTask->assertRedirect();
    $deleteTask->assertSessionHas('success', 'Event task removed.');

    $this->assertDatabaseMissing('event_tasks', [
        'id' => $task->id,
    ]);
});

test('event report pdf can be downloaded for an annual series event', function () {
    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');

    $event = Event::query()->create([
        'title' => 'Incubation Summit 2025',
        'event_type' => 'Summit',
        'annual_series_key' => 'incubation-summit',
        'event_year' => 2025,
        'is_annual' => true,
        'location' => 'Johannesburg',
        'start_date' => '2025-09-15',
        'end_date' => '2025-09-16',
        'status' => 'completed',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    Event::query()->create([
        'title' => 'Incubation Summit 2026',
        'event_type' => 'Summit',
        'annual_series_key' => 'incubation-summit',
        'event_year' => 2026,
        'is_annual' => true,
        'location' => 'Pretoria',
        'start_date' => '2026-09-15',
        'end_date' => '2026-09-16',
        'status' => 'planned',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    $event->participants()->create([
        'category' => 'speaker',
        'name' => 'Panel Speaker',
        'organization_name' => 'AB4IR',
        'topic' => 'Innovation',
        'attendance_status' => 'confirmed',
    ]);

    $event->participants()->create([
        'category' => 'attendee',
        'name' => 'Attendee One',
        'attendance_status' => 'attended',
    ]);

    $response = $this->actingAs($graph['user'])->get(route('events.report.pdf', $event->id));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('event managers can import participants from spreadsheet export and download registers', function () {
    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');

    $event = Event::query()->create([
        'title' => 'Import Ready Event',
        'event_type' => 'Expo',
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-10',
        'status' => 'planned',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    $csv = <<<'CSV'
Submission Time,PERSONAL DETAILS - Name - Prefix,PERSONAL DETAILS - Name - First Name,PERSONAL DETAILS - Name - Last Name,PERSONAL DETAILS - Email Address,PERSONAL DETAILS - Phone,PERSONAL DETAILS - How will you be attending the event?,PERSONAL DETAILS - Additional comments?
"May 14, 2026 @ 4:50 AM",Mr,Import,Speaker,speaker@example.com,0710001000,In-person (Physical Attendance),Imported from website
"May 14, 2026 @ 4:55 AM",Ms,Import,Guest,guest@example.com,0710001001,Virtual attendance,Pre-registered
CSV;

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $import = $this->actingAs($graph['user'])->post(route('events.participants.import', $event->id), [
        'file' => $file,
        'category_context' => 'speaker',
    ]);

    $import->assertRedirect();
    $import->assertSessionHas('success');

    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'category' => 'speaker',
        'name' => 'Import',
        'surname' => 'Speaker',
        'attendance_type' => 'In-person (Physical Attendance)',
    ]);
    $this->assertDatabaseHas('event_participants', [
        'event_id' => $event->id,
        'category' => 'speaker',
        'name' => 'Import',
        'surname' => 'Guest',
        'attendance_type' => 'Virtual attendance',
    ]);

    $show = $this->actingAs($graph['user'])->get(route('events.show', $event->id));
    $show->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.participant_summary.category_counts.speaker', 2)
            ->where('event.participant_summary.category_counts.attendee', 0)
            ->where('event.participant_summary.category_counts.media_house', 0)
            ->where('event.registers.0.key', 'speaker')
        );

    $pdf = $this->actingAs($graph['user'])->get(route('events.registers.pdf', [
        'event' => $event->id,
        'category' => 'speaker',
    ]));
    $pdf->assertOk();
    $pdf->assertHeader('content-type', 'application/pdf');

    $csvExport = $this->actingAs($graph['user'])->get(route('events.registers.csv', [
        'event' => $event->id,
        'category' => 'speaker',
    ]));
    $csvExport->assertOk();
    $csvExport->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('attendees require attendance type when captured manually', function () {
    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');

    $event = Event::query()->create([
        'title' => 'Attendance Type Validation Event',
        'event_type' => 'Forum',
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-10',
        'status' => 'planned',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    $response = $this->from(route('events.participants.page', $event->id))
        ->actingAs($graph['user'])
        ->post(route('events.participants.store', $event->id), [
            'category' => 'attendee',
            'name' => 'Attendance Missing',
            'organization_name' => 'AB4IR',
        ]);

    $response->assertRedirect(route('events.participants.page', $event->id));
    $response->assertSessionHasErrors(['attendance_type']);

    $this->assertDatabaseMissing('event_participants', [
        'event_id' => $event->id,
        'name' => 'Attendance Missing',
    ]);
});

test('event managers can capture post event reporting outcomes', function () {
    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');

    $event = Event::query()->create([
        'title' => 'Reported Event',
        'event_type' => 'Summit',
        'start_date' => '2026-11-11',
        'end_date' => '2026-11-12',
        'status' => 'completed',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    $response = $this->actingAs($graph['user'])->post(route('events.outcome-report.upsert', $event->id), [
        'summary' => 'Event delivered successfully.',
        'highlights' => 'Strong stakeholder attendance and media coverage.',
        'opportunities_created' => 'Captured future incubation referrals.',
        'partnerships_formed' => 'New collaboration with ecosystem partner.',
        'training_opportunities' => 'Training leads documented.',
        'media_coverage' => 'Livestream and media assets archived.',
        'statistics_summary' => 'Register data and attendance totals consolidated.',
        'thank_you_status' => 'Thank-you mailers sent.',
        'follow_up_actions' => 'Follow up with partners and attendees.',
        'report_status' => 'submitted',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Post-event report updated.');

    $this->assertDatabaseHas('event_outcome_reports', [
        'event_id' => $event->id,
        'report_status' => 'submitted',
        'summary' => 'Event delivered successfully.',
    ]);

    $show = $this->actingAs($graph['user'])->get(route('events.show', $event->id));
    $show->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.outcome_report.report_status', 'submitted')
            ->where('event.outcome_report.summary', 'Event delivered successfully.')
        );
});

test('event series and dedicated workflow pages can be viewed', function () {
    $graph = makeEventOwnerGraph();
    grantDomainAccess($graph['user'], 'events');

    $event = Event::query()->create([
        'title' => 'Digital Youth Festival',
        'event_type' => 'Festival',
        'annual_series_key' => 'digital-youth-festival',
        'event_year' => 2026,
        'is_annual' => true,
        'location' => 'Johannesburg',
        'start_date' => '2026-08-14',
        'end_date' => '2026-08-15',
        'status' => 'active',
        'owner_staff_member_id' => $graph['staff']->id,
    ]);

    app(\App\Domains\Events\Services\EventService::class)->ensurePlanningTemplate($event);
    $event->participants()->create([
        'category' => 'attendee',
        'name' => 'Festival Guest',
        'attendance_status' => 'confirmed',
    ]);

    $series = $this->actingAs($graph['user'])->get(route('events.series.show', 'digital-youth-festival'));
    $series->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Series')
            ->where('series.series_key', 'digital-youth-festival')
            ->where('series.years.0.id', $event->id)
        );

    $participants = $this->actingAs($graph['user'])->get(route('events.participants.page', $event->id));
    $participants->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Participants')
            ->where('event.id', $event->id)
        );

    $registers = $this->actingAs($graph['user'])->get(route('events.registers.page', $event->id));
    $registers->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Registers')
            ->where('event.id', $event->id)
        );

    $eventDay = $this->actingAs($graph['user'])->get(route('events.event-day', $event->id));
    $eventDay->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/EventDay')
            ->where('event.id', $event->id)
        );
});
