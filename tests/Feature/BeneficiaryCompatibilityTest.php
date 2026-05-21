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

uses(RefreshDatabase::class);

function makeBeneficiaryCompatibilityGraph(string $projectName = 'Compatibility Project', string $provinceName = 'Gauteng'): array
{
    $department = StaffDepartment::query()->first() ?? StaffDepartment::query()->create([
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
        'title' => $projectName.' Program',
        'description' => 'Programme for '.$projectName,
        'slug' => Str::slug($projectName.'-'.Str::random(5)),
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => $projectName,
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Operational project '.$projectName,
    ]);

    $province = Provinces::query()->create([
        'name' => $provinceName.' '.Str::upper(Str::random(4)),
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

    return compact('project', 'location', 'province');
}

function beneficiaryPayload(array $graph, array $overrides = []): array
{
    return array_merge([
        'name' => 'Ava',
        'surname' => 'Founder',
        'dob' => '2002-05-10',
        'age' => 22,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'street_address' => '10 Main Road',
        'address_line_2' => null,
        'city' => 'Johannesburg',
        'province_id' => $graph['province']->id,
        'postal_code' => '2000',
        'highest_qualification' => 'Diploma',
        'attendance_status' => 'active',
        'nok_name' => null,
        'nok_surname' => null,
        'nok_relationship' => null,
        'nok_phone' => null,
        'nok_email' => null,
    ], $overrides);
}

test('authorized user can create a beneficiary without next of kin details', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $graph = makeBeneficiaryCompatibilityGraph();

    $response = $this->actingAs($user)
        ->post('/beneficiaries', beneficiaryPayload($graph));

    $beneficiary = Beneficiary::query()->latest('id')->firstOrFail();

    $response->assertRedirect("/beneficiaries/{$beneficiary->id}");

    expect($beneficiary->next_of_kin_id)->toBeNull();
    expect($beneficiary->dob?->format('Y-m-d'))->toBe('2002-05-10');

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
    ]);
});

test('authorized user can create a beneficiary with partial legacy profile data', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $graph = makeBeneficiaryCompatibilityGraph('Legacy Import Project', 'North West');

    $response = $this->actingAs($user)
        ->post('/beneficiaries', beneficiaryPayload($graph, [
            'dob' => null,
            'age' => null,
            'id_number' => null,
            'email' => null,
            'gender' => null,
            'phone' => null,
        ]));

    $beneficiary = Beneficiary::query()->latest('id')->firstOrFail();

    $response->assertRedirect("/beneficiaries/{$beneficiary->id}");

    expect($beneficiary->dob)->toBeNull();
    expect($beneficiary->age)->toBeNull();
    expect($beneficiary->id_number)->toBeNull();
    expect($beneficiary->email)->toBeNull();
    expect($beneficiary->gender)->toBeNull();

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
    ]);
});

test('authorized user can clear next of kin details when updating a beneficiary', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $graph = makeBeneficiaryCompatibilityGraph('Transition Project', 'Limpopo');

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
        'project_id' => $graph['project']->id,
        'province_id' => $graph['province']->id,
        'postal_code' => '0700',
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->put("/beneficiaries/{$beneficiary->id}", beneficiaryPayload($graph, [
            'name' => $beneficiary->name,
            'surname' => $beneficiary->surname,
            'dob' => $beneficiary->dob?->format('Y-m-d'),
            'age' => $beneficiary->age,
            'id_number' => $beneficiary->id_number,
            'email' => $beneficiary->email,
            'phone' => $beneficiary->phone,
        ]))
        ->assertRedirect("/beneficiaries/{$beneficiary->id}");

    $beneficiary->refresh();

    expect($beneficiary->next_of_kin_id)->toBeNull();
    expect(NextOfKin::query()->whereKey($nextOfKin->id)->exists())->toBeFalse();
});

test('partial next of kin input is rejected when required identifying fields are missing', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $graph = makeBeneficiaryCompatibilityGraph('Validation Project', 'Mpumalanga');

    $this->actingAs($user)
        ->from('/beneficiaries')
        ->post('/beneficiaries', beneficiaryPayload($graph, [
            'nok_phone' => '0733333333',
        ]))
        ->assertRedirect('/beneficiaries')
        ->assertSessionHasErrors([
            'nok_name',
            'nok_surname',
            'nok_relationship',
        ]);
});
