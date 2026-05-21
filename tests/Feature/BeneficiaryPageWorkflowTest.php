<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\NextOfKin;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeBeneficiaryPageWorkflowFixture(): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Delivery',
        'description' => 'Delivery department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Project',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Delivery Programme',
        'description' => 'Programme for beneficiary workflow tests',
        'slug' => 'delivery-programme-'.Str::lower(Str::random(5)),
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Cohort 2026',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Test cohort',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Gauteng '.Str::upper(Str::random(4)),
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Fac',
        'surname' => 'ilitator',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '123 Street',
        'email' => 'facilitator-'.Str::lower(Str::random(8)).'@example.com',
        'cell' => '0712345678',
        'specialization' => 'Incubation',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Training Hall',
    ]);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Nora',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0733333333',
        'email' => 'nora.kin@example.test',
    ]);

    $beneficiary = Beneficiary::query()->create([
        'name' => 'Lebo',
        'surname' => 'Mokoena',
        'dob' => now()->subYears(24)->toDateString(),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'lebo-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $project->id,
        'province_id' => $province->id,
        'postal_code' => '2000',
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now()->subDay(),
    ]);

    return compact('program', 'project', 'province', 'location', 'beneficiary');
}

test('authorized user can open the beneficiary create page', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');
    $fixture = makeBeneficiaryPageWorkflowFixture();

    $this->actingAs($user)
        ->get('/beneficiaries/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Create')
            ->has('programs', 1)
            ->has('projects', 1)
            ->where('projects.0.id', $fixture['project']->id)
            ->has('projectLocations', 1)
        );
});

test('authorized user can open the beneficiary edit page', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');
    $fixture = makeBeneficiaryPageWorkflowFixture();

    $this->actingAs($user)
        ->get("/beneficiaries/{$fixture['beneficiary']->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Edit')
            ->where('beneficiary.id', $fixture['beneficiary']->id)
            ->where('beneficiary.full_name', 'Lebo Mokoena')
        );
});

test('deleting a beneficiary from the file page returns to the beneficiary index', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');
    $fixture = makeBeneficiaryPageWorkflowFixture();

    $enrollmentId = ProjectEnrollment::query()
        ->where('beneficiary_id', $fixture['beneficiary']->id)
        ->value('id');

    $nextOfKinId = $fixture['beneficiary']->next_of_kin_id;

    $this->actingAs($user)
        ->delete("/beneficiaries/{$fixture['beneficiary']->id}")
        ->assertRedirect('/beneficiaries');

    $this->assertSoftDeleted('beneficiaries', [
        'id' => $fixture['beneficiary']->id,
    ]);

    $this->assertDatabaseHas('project_enrollments', [
        'id' => $enrollmentId,
        'beneficiary_id' => $fixture['beneficiary']->id,
    ]);

    $this->assertDatabaseHas('next_of_kin', [
        'id' => $nextOfKinId,
    ]);
});

test('archived beneficiaries are excluded from the active beneficiary directory', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');
    $fixture = makeBeneficiaryPageWorkflowFixture();

    $fixture['beneficiary']->delete();

    $this->actingAs($user)
        ->get("/beneficiaries?program_id={$fixture['program']->id}&project_id={$fixture['project']->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Index')
            ->has('beneficiary.data', 0)
        );
});
