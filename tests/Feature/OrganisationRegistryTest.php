<?php

use App\Domains\Organisation\Models\Organisation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeOrganisationRegistryManager(): User
{
    $user = grantDomainAccess(User::factory()->create(), 'organization');

    $department = StaffDepartment::query()->create([
        'name' => 'Governance '.Str::upper(Str::random(4)),
        'description' => 'Governance and compliance',
    ]);

    StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Nandi',
        'last_name' => 'Registry',
        'email' => 'registry-'.Str::lower(Str::random(6)).'@example.com',
        'employee_number' => 'EMP-REG-'.Str::upper(Str::random(6)),
        'status' => 'active',
    ]);

    return $user;
}

test('organization managers can view the organisation registry page', function () {
    $user = makeOrganisationRegistryManager();

    Organisation::query()->create([
        'name' => 'Programme of Action NPC',
        'registration_number' => 'POA-001',
        'organisation_type' => 'NPC',
        'npo_number' => 'NPO-12345',
        'status' => 'active',
        'contact_details' => ['email' => 'info@example.com'],
    ]);

    $this->actingAs($user)
        ->get(route('organization.registry.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Organization/Registry')
            ->where('stats.total', 1)
            ->where('stats.active', 1)
            ->where('stats.npc', 1)
            ->where('stats.compliance_ready', 1)
            ->where('organisations.0.name', 'Programme of Action NPC')
        );
});

test('organization managers can create and update registry entities', function () {
    $user = makeOrganisationRegistryManager();

    $create = $this->actingAs($user)->post(route('organization.registry.store'), [
        'name' => 'Programme of Action Foundation',
        'registration_number' => 'POA-FOUND-001',
        'organisation_type' => 'NPC',
        'npo_number' => 'NPO-9001',
        'pbo_number' => 'PBO-2201',
        'tax_reference_number' => 'TAX-4455',
        'constitution_version' => '2026.1',
        'registered_at' => '2026-01-10',
        'status' => 'active',
        'contact_details' => [
            'contact_person' => 'Registry Lead',
            'email' => 'registry@example.com',
            'phone' => '0110000000',
        ],
    ]);

    $create->assertRedirect();
    $create->assertSessionHas('success', 'Organisation added to registry.');

    $organisation = Organisation::query()->firstOrFail();

    $this->assertDatabaseHas('organisations', [
        'id' => $organisation->id,
        'name' => 'Programme of Action Foundation',
        'registration_number' => 'POA-FOUND-001',
    ]);

    $update = $this->actingAs($user)->put(route('organization.registry.update', $organisation->id), [
        'name' => 'Programme of Action Foundation',
        'registration_number' => 'POA-FOUND-001',
        'organisation_type' => 'Hybrid',
        'npo_number' => 'NPO-9001',
        'pbo_number' => 'PBO-2201',
        'tax_reference_number' => 'TAX-4455',
        'constitution_version' => '2026.2',
        'registered_at' => '2026-01-10',
        'status' => 'active',
        'contact_details' => [
            'contact_person' => 'Executive Registry Lead',
            'email' => 'registry@example.com',
            'phone' => '0110000000',
        ],
    ]);

    $update->assertRedirect();
    $update->assertSessionHas('success', 'Organisation registry entry updated.');

    $this->assertDatabaseHas('organisations', [
        'id' => $organisation->id,
        'organisation_type' => 'Hybrid',
        'constitution_version' => '2026.2',
    ]);
});

test('dashboard exposes the organisation registry widget for organization users', function () {
    $user = makeOrganisationRegistryManager();

    Organisation::query()->create([
        'name' => 'Programme of Action NPC',
        'registration_number' => 'POA-ORG-001',
        'organisation_type' => 'NPC',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.secondary.0.key', 'organisation-registry')
            ->where('dashboard.secondary.0.title', 'Organisation registry')
        );
});
