<?php

use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Events\Models\Event;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeStakeholderWorkspaceUser(): User
{
    $user = grantPermissions(User::factory()->create([
        'email' => 'stakeholder.docs@example.test',
        'name' => 'stakeholder.docs',
    ]), ['domain.stakeholders.view', 'domain.stakeholders.manage']);

    $department = StaffDepartment::query()->create([
        'name' => 'Stakeholder Workspace',
        'description' => 'Stakeholder workspace department',
    ]);

    $staff = StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Stakeholder',
        'last_name' => 'Manager',
        'email' => 'stakeholder.docs@example.test',
        'phone' => '0711111111',
        'employee_number' => 'STKDOC01',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => true,
        'is_ceo' => false,
    ]);

    $user->staffMember()->save($staff);

    return $user->refresh();
}

test('stakeholder manager can create a stakeholder workspace from library root', function () {
    $user = makeStakeholderWorkspaceUser();

    $stakeholder = Stakeholder::query()->create([
        'organization_name' => 'Future Labs',
        'name' => 'Future Labs Primary',
        'email' => 'future@example.test',
        'contact_number' => '0110000000',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->post(route('organization.document-library.root-folders.store'), [
            'name' => 'Future Labs Workspace',
            'owner_type' => Stakeholder::class,
            'owner_id' => $stakeholder->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('document_folders', [
        'name' => 'Future Labs Workspace',
        'owner_type' => Stakeholder::class,
        'owner_id' => $stakeholder->id,
    ]);

    $group = DocumentFolder::query()->where('name', 'Stakeholders')->whereNull('parent_id')->first();
    expect($group)->not->toBeNull();
});

test('event manager can create an event workspace from library root', function () {
    $user = grantPermissions(User::factory()->create([
        'email' => 'event.workspace.docs@example.test',
        'name' => 'event.workspace.docs',
    ]), ['domain.events.view', 'domain.events.manage']);

    $event = Event::query()->create([
        'title' => 'Founder Breakfast',
        'event_type' => 'networking',
        'event_format' => 'in_person',
        'location' => 'Johannesburg',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Workspace validation coverage.',
    ]);

    $this->actingAs($user)
        ->post(route('organization.document-library.root-folders.store'), [
            'name' => 'Founder Breakfast Workspace',
            'owner_type' => Event::class,
            'owner_id' => $event->id,
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('document_folders', [
        'name' => 'Founder Breakfast Workspace',
        'owner_type' => Event::class,
        'owner_id' => $event->id,
    ]);

    $group = DocumentFolder::query()->where('name', 'Events')->whereNull('parent_id')->first();
    expect($group)->not->toBeNull();
});
