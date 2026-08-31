<?php

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createLmsFacilitatorLookupFixture(string $email = 'lookup.facilitator@example.test'): array
{
    $program = Program::query()->create([
        'title' => 'Facilitator Bridge Programme',
        'description' => 'Facilitator bridge test programme',
        'slug' => 'facilitator-bridge-programme',
    ]);

    $department = StaffDepartment::query()->create([
        'name' => 'Facilitator Bridge Department',
        'description' => 'Facilitator Bridge Department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Bridge',
        'last_name' => 'Manager',
        'email' => fake()->unique()->safeEmail(),
        'employee_number' => 'FAC-'.fake()->unique()->numerify('###'),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => true,
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Facilitator Bridge Cohort',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $province = Provinces::query()->create(['name' => 'Gauteng']);

    $facilitator = Facilitator::query()->create([
        'name' => 'Lookup',
        'surname' => 'Facilitator',
        'dob' => '1990-01-01',
        'id_number' => fake()->unique()->numerify('#############'),
        'address' => '1 Bridge Street',
        'email' => $email,
        'cell' => '0730000000',
        'specialization' => 'Digital Skills',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Innovation Lab A',
    ]);

    return compact('program', 'project', 'location', 'facilitator');
}

test('authorized facilitator viewer can search ERP facilitators eligible for LMS provisioning', function () {
    $user = grantDomainAccess(User::factory()->create(), 'facilitators', manage: false);
    $fixture = createLmsFacilitatorLookupFixture('facilitator.target@example.test');

    $this->actingAs($user)
        ->getJson('/integrations/lms/facilitators/lookup?search=facilitator.target@example.test')
        ->assertOk()
        ->assertJsonPath('data.0.erp_facilitator_id', (string) $fixture['facilitator']->id)
        ->assertJsonPath('data.0.email', 'facilitator.target@example.test')
        ->assertJsonPath('data.0.specialization', 'Digital Skills')
        ->assertJsonPath('data.0.assignments.0.project.name', 'Facilitator Bridge Cohort')
        ->assertJsonMissingPath('data.0.id_number')
        ->assertJsonMissingPath('data.0.address')
        ->assertJsonMissingPath('data.0.dob');
});

test('LMS facilitator lookup requires ERP facilitator view permission', function () {
    $user = User::factory()->create();
    createLmsFacilitatorLookupFixture();

    $this->actingAs($user)
        ->getJson('/integrations/lms/facilitators/lookup?search=lookup')
        ->assertForbidden();
});

test('LMS facilitator lookup allows configured bridge token without browser login', function () {
    config(['services.lms_bridge.token' => 'test-lms-token']);
    $fixture = createLmsFacilitatorLookupFixture('token.facilitator@example.test');

    $this->withHeader('X-LMS-BRIDGE-TOKEN', 'test-lms-token')
        ->getJson('/integrations/lms/facilitators/lookup?search=token.facilitator@example.test')
        ->assertOk()
        ->assertJsonPath('data.0.erp_facilitator_id', (string) $fixture['facilitator']->id);
});
