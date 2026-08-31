<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createLmsLookupFixture(string $email = 'lookup.beneficiary@example.test', string $status = 'enrolled', string $attendanceStatus = 'active'): array
{
    $program = Program::query()->create([
        'title' => 'LMS Bridge Programme',
        'description' => 'LMS bridge test programme',
        'slug' => 'lms-bridge-programme',
    ]);

    $department = StaffDepartment::query()->create([
        'name' => 'LMS Bridge Department',
        'description' => 'LMS Bridge Department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'LMS',
        'last_name' => 'Manager',
        'email' => fake()->unique()->safeEmail(),
        'employee_number' => 'LMS-'.fake()->unique()->numerify('###'),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => true,
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'LMS Bridge Cohort',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Gauteng',
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'LMS',
        'surname' => 'Facilitator',
        'dob' => '1990-01-01',
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '1 Bridge Street',
        'email' => fake()->unique()->safeEmail(),
        'cell' => '0711111111',
        'specialization' => 'Training',
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
    ]);

    $beneficiary = Beneficiary::query()->create([
        'name' => 'Lookup',
        'surname' => 'Beneficiary',
        'dob' => now()->subYears(22),
        'age' => 22,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => $email,
        'phone' => '0720000000',
        'gender' => 'female',
        'project_id' => $project->id,
        'attendance_status' => $attendanceStatus,
        'status' => $status,
    ]);

    $enrollment = ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => $status === 'enrolled' ? 'enrolled' : 'dropped',
        'enrolled_at' => now()->subDay(),
    ]);

    return compact('program', 'project', 'location', 'beneficiary', 'enrollment');
}

test('authorized beneficiary viewer can search ERP beneficiaries eligible for LMS provisioning', function () {
    $user = grantDomainAccess(User::factory()->create(), 'beneficiaries', manage: false);
    $fixture = createLmsLookupFixture('lookup.target@example.test');

    $this->actingAs($user)
        ->getJson('/integrations/lms/beneficiaries/lookup?search=lookup.target@example.test')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'erp_beneficiary_id',
                    'erp_project_enrollment_id',
                    'name',
                    'email',
                    'status',
                    'attendance_status',
                    'project',
                    'programme',
                    'location',
                    'synced_at',
                ],
            ],
        ])
        ->assertJsonPath('data.0.erp_beneficiary_id', (string) $fixture['beneficiary']->id)
        ->assertJsonPath('data.0.erp_project_enrollment_id', (string) $fixture['enrollment']->id)
        ->assertJsonPath('data.0.email', 'lookup.target@example.test')
        ->assertJsonPath('data.0.project.name', 'LMS Bridge Cohort')
        ->assertJsonPath('data.0.programme.title', 'LMS Bridge Programme');
});

test('LMS beneficiary lookup excludes inactive lifecycle records', function () {
    $user = grantDomainAccess(User::factory()->create(), 'beneficiaries', manage: false);
    createLmsLookupFixture('exited.target@example.test', 'exited');

    $this->actingAs($user)
        ->getJson('/integrations/lms/beneficiaries/lookup?search=exited.target@example.test')
        ->assertOk()
        ->assertJsonPath('data', []);
});

test('LMS beneficiary lookup requires ERP beneficiary view permission', function () {
    $user = User::factory()->create();
    createLmsLookupFixture();

    $this->actingAs($user)
        ->getJson('/integrations/lms/beneficiaries/lookup?search=lookup')
        ->assertForbidden();
});

test('LMS beneficiary lookup allows configured bridge token without browser login', function () {
    config(['services.lms_bridge.token' => 'test-lms-token']);
    $fixture = createLmsLookupFixture('token.lookup@example.test');

    $this->withHeader('X-LMS-BRIDGE-TOKEN', 'test-lms-token')
        ->getJson('/integrations/lms/beneficiaries/lookup?search=token.lookup@example.test')
        ->assertOk()
        ->assertJsonPath('data.0.erp_beneficiary_id', (string) $fixture['beneficiary']->id);
});
