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
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createBeneficiaryProjectFixture(string $programTitle, string $projectName, string $beneficiaryEmail, string $provinceName, string $status = 'enrolled'): array
{
    $program = Program::query()->firstOrCreate(
        ['slug' => str($programTitle)->slug()->value()],
        [
            'title' => $programTitle,
            'description' => "{$programTitle} description",
        ]
    );

    $department = StaffDepartment::query()->create([
        'name' => "{$projectName} Department",
        'description' => "{$projectName} Department",
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Project',
        'last_name' => 'Manager',
        'email' => str($projectName)->slug()->value().'.manager@example.test',
        'employee_number' => 'PM-'.fake()->unique()->numerify('###'),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => true,
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => $projectName,
        'start_date' => now()->toDateString(),
        'status' => 'planned',
    ]);

    $province = Provinces::query()->create([
        'name' => $provinceName,
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Site',
        'surname' => 'Facilitator',
        'dob' => '1990-01-01',
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '1 Venue Street',
        'email' => fake()->unique()->safeEmail(),
        'cell' => '0711111111',
        'specialization' => 'Training',
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
    ]);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Next',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0712222222',
        'email' => fake()->unique()->safeEmail(),
    ]);

    $beneficiary = Beneficiary::query()->create([
        'name' => fake()->firstName(),
        'surname' => fake()->lastName(),
        'dob' => now()->subYears(24),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => $beneficiaryEmail,
        'phone' => '0720000000',
        'gender' => 'female',
        'project_id' => $project->id,
        'next_of_kin_id' => $nextOfKin->id,
        'attendance_status' => $status === 'dropped' ? 'dropout' : 'active',
    ]);

    $enrollment = ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => $status,
        'enrolled_at' => now()->subDays(10),
    ]);

    return compact('program', 'project', 'province', 'location', 'beneficiary', 'enrollment');
}

test('beneficiary index drills down by program and project iteration', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $alpha = createBeneficiaryProjectFixture(
        'Digital Incubation',
        'Digital Incubation Cohort 2026',
        'alpha.beneficiary@example.test',
        'Gauteng'
    );

    createBeneficiaryProjectFixture(
        'Digital Incubation',
        'Digital Incubation Cohort 2025',
        'beta.beneficiary@example.test',
        'KwaZulu-Natal'
    );

    createBeneficiaryProjectFixture(
        'Creative Labs',
        'Creative Labs Cohort 2026',
        'gamma.beneficiary@example.test',
        'Western Cape'
    );

    $this->actingAs($user)
        ->get("/beneficiaries?program_id={$alpha['program']->id}&project_id={$alpha['project']->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Index')
            ->where('selectedProgramId', $alpha['program']->id)
            ->where('selectedProjectId', $alpha['project']->id)
            ->has('filterProjects', 2)
            ->has('beneficiary.data', 1)
            ->where('beneficiary.data.0.id', $alpha['beneficiary']->id)
            ->where('beneficiary.data.0.program_title', 'Digital Incubation')
            ->where('beneficiary.data.0.project_name', 'Digital Incubation Cohort 2026')
        );
});

test('beneficiary index stays empty until a project iteration is selected', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    createBeneficiaryProjectFixture(
        'Digital Incubation',
        'Digital Incubation Cohort 2026',
        'alpha.beneficiary@example.test',
        'Gauteng'
    );

    $this->actingAs($user)
        ->get('/beneficiaries')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Index')
            ->where('selectedProgramId', null)
            ->where('selectedProjectId', null)
            ->has('beneficiary.data', 0)
        );
});

test('beneficiary show page exposes participation history as a beneficiary file', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $first = createBeneficiaryProjectFixture(
        'Animation Track',
        'Animation Track Cohort 2025',
        'history.beneficiary@example.test',
        'Limpopo',
        'completed'
    );

    $secondProgram = Program::query()->create([
        'title' => 'Animation Track Advanced',
        'description' => 'Advanced animation support',
        'slug' => 'animation-track-advanced',
    ]);

    $secondProject = Project::query()->create([
        'program_id' => $secondProgram->id,
        'project_manager_id' => $first['project']->project_manager_id,
        'name' => 'Animation Track Cohort 2026',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $secondProvince = Provinces::query()->create([
        'name' => 'Mpumalanga',
    ]);

    $secondFacilitator = Facilitator::query()->create([
        'name' => 'Progress',
        'surname' => 'Facilitator',
        'dob' => '1991-02-01',
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '2 Venue Street',
        'email' => fake()->unique()->safeEmail(),
        'cell' => '0733333333',
        'specialization' => 'Mentorship',
    ]);

    $secondLocation = ProjectLocation::query()->create([
        'project_id' => $secondProject->id,
        'facilitator_id' => $secondFacilitator->id,
        'province_id' => $secondProvince->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $secondProject->id,
        'project_location_id' => $secondLocation->id,
        'beneficiary_id' => $first['beneficiary']->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    $first['beneficiary']->update([
        'project_id' => $secondProject->id,
        'attendance_status' => 'active',
    ]);

    $this->actingAs($user)
        ->get("/beneficiaries/{$first['beneficiary']->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Show')
            ->where('beneficiary.id', $first['beneficiary']->id)
            ->where('beneficiary.current_participation.project_name', 'Animation Track Cohort 2026')
            ->has('beneficiary.participation_history', 2)
            ->where('beneficiary.participation_history.0.program_title', 'Animation Track Advanced')
            ->where('beneficiary.participation_history.1.project_name', 'Animation Track Cohort 2025')
        );
});

test('beneficiary resource uses the current project enrollment for location details after project transfer', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $first = createBeneficiaryProjectFixture(
        'Animation Track',
        'Animation Track Cohort 2025',
        'latest-location@example.test',
        'Limpopo'
    );

    $secondProgram = Program::query()->create([
        'title' => 'Animation Track Advanced',
        'description' => 'Advanced animation support',
        'slug' => 'animation-track-advanced',
    ]);

    $secondProject = Project::query()->create([
        'program_id' => $secondProgram->id,
        'project_manager_id' => $first['project']->project_manager_id,
        'name' => 'Animation Track Cohort 2026',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $newProvince = Provinces::query()->create([
        'name' => 'Mpumalanga',
    ]);

    $newFacilitator = Facilitator::query()->create([
        'name' => 'Updated',
        'surname' => 'Facilitator',
        'dob' => '1991-03-01',
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '3 Venue Street',
        'email' => fake()->unique()->safeEmail(),
        'cell' => '0744444444',
        'specialization' => 'Mentorship',
    ]);

    $newLocation = ProjectLocation::query()->create([
        'project_id' => $secondProject->id,
        'facilitator_id' => $newFacilitator->id,
        'province_id' => $newProvince->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $secondProject->id,
        'project_location_id' => $newLocation->id,
        'beneficiary_id' => $first['beneficiary']->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    $first['beneficiary']->update([
        'project_id' => $secondProject->id,
        'attendance_status' => 'active',
    ]);

    $this->actingAs($user)
        ->get("/beneficiaries/{$first['beneficiary']->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Show')
            ->where('beneficiary.project_location_id', $newLocation->id)
            ->where('beneficiary.project_location', 'Mpumalanga')
            ->where('beneficiary.current_participation.location_id', $newLocation->id)
            ->where('beneficiary.current_participation.location_name', 'Mpumalanga')
            ->where('beneficiary.participation_history.0.location_id', $newLocation->id)
            ->where('beneficiary.participation_history.0.location_name', 'Mpumalanga')
            ->where('beneficiary.participation_history.1.location_name', 'Limpopo')
        );
});
