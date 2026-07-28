<?php

use App\Domains\Committees\Models\Committee;
use App\Domains\Governance\Models\GovernanceStructure;
use App\Domains\Meetings\Models\Meeting;
use App\Domains\Organisation\Models\Organisation;
use App\Domains\Resolutions\Models\Resolution;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeGovernanceManager(array $domains = ['governance', 'committees', 'meetings', 'resolutions']): User
{
    $user = User::factory()->create();

    foreach ($domains as $domain) {
        grantDomainAccess($user, $domain);
    }

    $department = StaffDepartment::query()->create([
        'name' => 'Governance '.Str::upper(Str::random(4)),
        'description' => 'Governance operations',
    ]);

    StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Aphiwe',
        'last_name' => 'Governance',
        'email' => 'governance-'.Str::lower(Str::random(6)).'@example.com',
        'employee_number' => 'EMP-GOV-'.Str::upper(Str::random(6)),
        'status' => 'active',
    ]);

    return $user->refresh();
}

test('governance managers can view and manage governance structures', function () {
    $user = makeGovernanceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action NPC',
        'registration_number' => 'POA-GOV-001',
        'organisation_type' => 'NPC',
        'status' => 'active',
    ]);

    GovernanceStructure::query()->create([
        'organisation_id' => $organisation->id,
        'name' => 'Board',
        'description' => 'Primary board structure',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route('governance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Governance/Index')
            ->where('stats.total', 1)
            ->where('structures.0.name', 'Board')
        );

    $this->actingAs($user)->post(route('governance.store'), [
        'organisation_id' => $organisation->id,
        'name' => 'Executive Committee',
        'description' => 'Executive oversight',
        'status' => 'active',
    ])->assertRedirect()->assertSessionHas('success', 'Governance structure added.');

    $structure = GovernanceStructure::query()->where('name', 'Executive Committee')->firstOrFail();

    $this->actingAs($user)->put(route('governance.update', $structure->id), [
        'organisation_id' => $organisation->id,
        'name' => 'Executive Committee',
        'description' => 'Executive oversight and delegated authority',
        'status' => 'inactive',
    ])->assertRedirect()->assertSessionHas('success', 'Governance structure updated.');

    $this->assertDatabaseHas('governance_structures', [
        'id' => $structure->id,
        'status' => 'inactive',
    ]);
});

test('committee managers can view and manage committees', function () {
    $user = makeGovernanceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action Foundation',
        'registration_number' => 'POA-COM-001',
        'organisation_type' => 'Foundation',
        'status' => 'active',
    ]);

    $chairperson = User::factory()->create(['name' => 'Board Chair']);

    $this->actingAs($user)->post(route('committees.store'), [
        'organisation_id' => $organisation->id,
        'name' => 'Finance Committee',
        'description' => 'Funding and finance governance',
        'chairperson_id' => $chairperson->id,
        'secretary_id' => $chairperson->id,
        'status' => 'active',
    ])->assertRedirect()->assertSessionHas('success', 'Committee added.');

    $committee = Committee::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('committees.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Committees/Index')
            ->where('stats.total', 1)
            ->where('committees.0.name', 'Finance Committee')
        );

    $this->actingAs($user)->put(route('committees.update', $committee->id), [
        'organisation_id' => $organisation->id,
        'name' => 'Finance Committee',
        'description' => 'Funding, audit, and finance governance',
        'chairperson_id' => $chairperson->id,
        'secretary_id' => $chairperson->id,
        'status' => 'inactive',
    ])->assertRedirect()->assertSessionHas('success', 'Committee updated.');

    $this->assertDatabaseHas('committees', [
        'id' => $committee->id,
        'status' => 'inactive',
    ]);
});

test('meeting managers can view and manage meetings', function () {
    $user = makeGovernanceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action Trust',
        'registration_number' => 'POA-MTG-001',
        'organisation_type' => 'Trust',
        'status' => 'active',
    ]);

    $committee = Committee::query()->create([
        'organisation_id' => $organisation->id,
        'name' => 'Audit Committee',
        'status' => 'active',
    ]);

    $this->actingAs($user)->post(route('meetings.store'), [
        'organisation_id' => $organisation->id,
        'committee_id' => $committee->id,
        'meeting_number' => 'AC-2026-01',
        'title' => 'Quarter 1 Audit Review',
        'meeting_date' => now()->addWeek()->toDateString(),
        'location' => 'Board Room',
        'agenda' => 'Review compliance and controls.',
        'minutes' => '',
        'status' => 'scheduled',
    ])->assertRedirect()->assertSessionHas('success', 'Meeting added.');

    $meeting = Meeting::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('meetings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Meetings/Index')
            ->where('stats.total', 1)
            ->where('meetings.0.title', 'Quarter 1 Audit Review')
        );

    $this->actingAs($user)->put(route('meetings.update', $meeting->id), [
        'organisation_id' => $organisation->id,
        'committee_id' => $committee->id,
        'meeting_number' => 'AC-2026-01',
        'title' => 'Quarter 1 Audit Review',
        'meeting_date' => now()->addWeek()->toDateString(),
        'location' => 'Executive Board Room',
        'agenda' => 'Review compliance and controls.',
        'minutes' => 'Actions captured.',
        'status' => 'completed',
    ])->assertRedirect()->assertSessionHas('success', 'Meeting updated.');

    $this->assertDatabaseHas('meetings', [
        'id' => $meeting->id,
        'status' => 'completed',
    ]);
});

test('resolution managers can view and manage resolutions', function () {
    $user = makeGovernanceManager();

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action Association',
        'registration_number' => 'POA-RES-001',
        'organisation_type' => 'Association',
        'status' => 'active',
    ]);

    $meeting = Meeting::query()->create([
        'organisation_id' => $organisation->id,
        'meeting_number' => 'BOARD-2026-01',
        'title' => 'Board Meeting',
        'meeting_date' => now()->subDays(2)->toDateString(),
        'status' => 'completed',
    ]);

    $owner = User::factory()->create(['name' => 'Resolution Owner']);

    $this->actingAs($user)->post(route('resolutions.store'), [
        'organisation_id' => $organisation->id,
        'meeting_id' => $meeting->id,
        'resolution_number' => 'RES-2026-001',
        'title' => 'Approve annual compliance plan',
        'description' => 'Finalise and circulate the annual compliance plan.',
        'owner_id' => $owner->id,
        'due_date' => now()->addDays(14)->toDateString(),
        'status' => 'open',
    ])->assertRedirect()->assertSessionHas('success', 'Resolution added.');

    $resolution = Resolution::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('resolutions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Resolutions/Index')
            ->where('stats.total', 1)
            ->where('resolutions.0.title', 'Approve annual compliance plan')
        );

    $this->actingAs($user)->put(route('resolutions.update', $resolution->id), [
        'organisation_id' => $organisation->id,
        'meeting_id' => $meeting->id,
        'resolution_number' => 'RES-2026-001',
        'title' => 'Approve annual compliance plan',
        'description' => 'Finalised and issued.',
        'owner_id' => $owner->id,
        'due_date' => now()->addDays(14)->toDateString(),
        'status' => 'completed',
    ])->assertRedirect()->assertSessionHas('success', 'Resolution updated.');

    $this->assertDatabaseHas('resolutions', [
        'id' => $resolution->id,
        'status' => 'completed',
    ]);
});

test('dashboard exposes governance widget for governance users', function () {
    $user = makeGovernanceManager(['governance', 'meetings', 'resolutions']);

    $organisation = Organisation::query()->create([
        'name' => 'Programme of Action NPC',
        'registration_number' => 'POA-DASH-001',
        'organisation_type' => 'NPC',
        'status' => 'active',
    ]);

    GovernanceStructure::query()->create([
        'organisation_id' => $organisation->id,
        'name' => 'Board',
        'status' => 'active',
    ]);

    $meeting = Meeting::query()->create([
        'organisation_id' => $organisation->id,
        'meeting_number' => 'BOARD-2026-02',
        'title' => 'Board Review',
        'meeting_date' => now()->addDays(5)->toDateString(),
        'status' => 'scheduled',
    ]);

    Resolution::query()->create([
        'organisation_id' => $organisation->id,
        'meeting_id' => $meeting->id,
        'resolution_number' => 'RES-2026-002',
        'title' => 'Submit board pack',
        'status' => 'open',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.secondary.0.key', 'governance')
            ->where('dashboard.secondary.0.title', 'Governance widget')
            ->where('dashboard.secondary.0.value', 1)
        );
});
