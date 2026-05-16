<?php

use App\Domains\Beneficiaries\Services\BeneficiaryService;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Services\ProjectEnrollmentService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeProjectSupportGraph(string $projectName, string $provinceName): array
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

    return compact('project', 'location');
}

test('beneficiary service rejects locations that do not belong to the selected project', function () {
    $this->actingAs(User::factory()->create());
    $primary = makeProjectSupportGraph('Primary Project', 'Gauteng');
    $secondary = makeProjectSupportGraph('Secondary Project', 'Limpopo');

    $service = app(BeneficiaryService::class);

    expect(fn () => $service->store([
        'name' => 'Ava',
        'surname' => 'Founder',
        'dob' => now()->subYears(22)->toDateString(),
        'age' => 22,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $primary['project']->id,
        'project_location_id' => $secondary['location']->id,
        'street_address' => '10 Main Road',
        'address_line_2' => null,
        'city' => 'Johannesburg',
        'province_id' => null,
        'postal_code' => '2000',
        'highest_qualification' => 'Diploma',
        'attendance_status' => 'active',
        'nok_name' => 'Nora',
        'nok_surname' => 'Kin',
        'nok_relationship' => 'Sibling',
        'nok_phone' => '0733333333',
        'nok_email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]))->toThrow(ValidationException::class);
});

test('beneficiary project transfer drops prior active enrollment and syncs current project enrollment', function () {
    $this->actingAs(User::factory()->create());
    $first = makeProjectSupportGraph('Alpha Project', 'North West');
    $second = makeProjectSupportGraph('Beta Project', 'Free State');

    $service = app(BeneficiaryService::class);

    $beneficiary = $service->store([
        'name' => 'Lebo',
        'surname' => 'Mokoena',
        'dob' => now()->subYears(24)->toDateString(),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $first['project']->id,
        'project_location_id' => $first['location']->id,
        'street_address' => '10 Main Road',
        'address_line_2' => null,
        'city' => 'Mahikeng',
        'province_id' => null,
        'postal_code' => '2745',
        'highest_qualification' => 'Certificate',
        'attendance_status' => 'active',
        'nok_name' => 'Nora',
        'nok_surname' => 'Kin',
        'nok_relationship' => 'Sibling',
        'nok_phone' => '0733333333',
        'nok_email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $updated = $service->update($beneficiary->id, [
        'name' => 'Lebo',
        'surname' => 'Mokoena',
        'dob' => now()->subYears(24)->toDateString(),
        'age' => 24,
        'id_number' => $beneficiary->id_number,
        'email' => $beneficiary->email,
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $second['project']->id,
        'project_location_id' => $second['location']->id,
        'street_address' => '10 Main Road',
        'address_line_2' => null,
        'city' => 'Bloemfontein',
        'province_id' => null,
        'postal_code' => '9300',
        'highest_qualification' => 'Certificate',
        'attendance_status' => 'active',
        'nok_name' => 'Nora',
        'nok_surname' => 'Kin',
        'nok_relationship' => 'Sibling',
        'nok_phone' => '0733333333',
        'nok_email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $first['project']->id,
        'beneficiary_id' => $updated->id,
        'status' => 'dropped',
    ]);

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $second['project']->id,
        'beneficiary_id' => $updated->id,
        'project_location_id' => $second['location']->id,
        'status' => 'enrolled',
    ]);
});

test('project enrollment service rejects beneficiaries assigned to a different project', function () {
    $this->actingAs(User::factory()->create());
    $first = makeProjectSupportGraph('Gamma Project', 'Mpumalanga');
    $second = makeProjectSupportGraph('Delta Project', 'Limpopo');

    $beneficiary = app(BeneficiaryService::class)->store([
        'name' => 'Neo',
        'surname' => 'Dlamini',
        'dob' => now()->subYears(25)->toDateString(),
        'age' => 25,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'male',
        'project_id' => $first['project']->id,
        'project_location_id' => $first['location']->id,
        'street_address' => '11 Main Road',
        'address_line_2' => null,
        'city' => 'Mbombela',
        'province_id' => null,
        'postal_code' => '1200',
        'highest_qualification' => 'Degree',
        'attendance_status' => 'active',
        'nok_name' => 'Nora',
        'nok_surname' => 'Kin',
        'nok_relationship' => 'Sibling',
        'nok_phone' => '0733333333',
        'nok_email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $service = app(ProjectEnrollmentService::class);

    expect(fn () => $service->createEnrollment([
        'project_id' => $second['project']->id,
        'project_location_id' => $second['location']->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now()->toDateString(),
    ]))->toThrow(ValidationException::class);

    expect(ProjectEnrollment::query()->where('beneficiary_id', $beneficiary->id)->count())->toBe(1);
});
