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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeBeneficiaryImportGraph(string $projectName, string $provinceName): array
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

test('beneficiary import creates new records and reports same-project matches', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $graph = makeBeneficiaryImportGraph('Import Cohort', 'Gauteng');

    $existing = Beneficiary::query()->create([
        'name' => 'Lebo',
        'surname' => 'Mokoena',
        'dob' => '2002-05-10',
        'age' => 22,
        'id_number' => '0205100001088',
        'email' => 'lebo.existing@example.test',
        'phone' => '0721111111',
        'gender' => 'female',
        'project_id' => $graph['project']->id,
        'attendance_status' => 'active',
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'beneficiary_id' => $existing->id,
        'status' => 'enrolled',
        'enrolled_at' => now()->subDay(),
    ]);

    $csv = <<<'CSV'
name,surname,dob,id_number,email,phone,gender,city
Lebo,Mokoena,2002-05-10,0205100001088,lebo.existing@example.test,0721111111,female,Johannesburg
Neo,Dlamini,2001-04-18,0104180001088,neo.new@example.test,0722222222,male,Pretoria
CSV;

    $file = UploadedFile::fake()->createWithContent('beneficiaries.csv', $csv);

    $response = $this->actingAs($user)->post(route('beneficiaries.import'), [
        'file' => $file,
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('Created: 1');
    expect(session('success'))->toContain('Matched existing: 1');

    $this->assertDatabaseHas('beneficiaries', [
        'name' => 'Neo',
        'surname' => 'Dlamini',
        'project_id' => $graph['project']->id,
    ]);
});

test('beneficiary import rejects duplicates that belong to another project', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $first = makeBeneficiaryImportGraph('Source Cohort', 'Limpopo');
    $second = makeBeneficiaryImportGraph('Target Cohort', 'Mpumalanga');

    $existing = Beneficiary::query()->create([
        'name' => 'Ava',
        'surname' => 'Founder',
        'dob' => '2002-05-10',
        'age' => 22,
        'id_number' => '0205100001088',
        'email' => 'ava.source@example.test',
        'phone' => '0721111111',
        'gender' => 'female',
        'project_id' => $first['project']->id,
        'attendance_status' => 'active',
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $first['project']->id,
        'project_location_id' => $first['location']->id,
        'beneficiary_id' => $existing->id,
        'status' => 'enrolled',
        'enrolled_at' => now()->subDay(),
    ]);

    $csv = <<<'CSV'
name,surname,dob,id_number,email
Ava,Founder,2002-05-10,0205100001088,ava.source@example.test
CSV;

    $file = UploadedFile::fake()->createWithContent('beneficiaries.csv', $csv);

    $response = $this->actingAs($user)->post(route('beneficiaries.import'), [
        'file' => $file,
        'project_id' => $second['project']->id,
        'project_location_id' => $second['location']->id,
    ]);

    $response->assertRedirect();
    expect(session('success'))->toContain('Rejected duplicates: 1');
    expect(session('import_errors'))->not->toBeEmpty();
    expect(Beneficiary::query()->count())->toBe(1);
});

test('beneficiary import rejects archived duplicate identities', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'beneficiaries');

    $graph = makeBeneficiaryImportGraph('Archive Import Cohort', 'North West');

    $archived = Beneficiary::query()->create([
        'name' => 'Mpho',
        'surname' => 'Archive',
        'dob' => '2000-01-01',
        'age' => 25,
        'id_number' => '0001010001088',
        'email' => 'mpho.archive@example.test',
        'phone' => '0721111111',
        'gender' => 'female',
        'project_id' => $graph['project']->id,
        'attendance_status' => 'active',
    ]);

    $archived->delete();

    $csv = <<<'CSV'
name,surname,dob,id_number,email
Mpho,Archive,2000-01-01,0001010001088,mpho.archive@example.test
CSV;

    $file = UploadedFile::fake()->createWithContent('beneficiaries.csv', $csv);

    $response = $this->actingAs($user)->post(route('beneficiaries.import'), [
        'file' => $file,
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
    ]);

    $response->assertRedirect();
    expect(session('success'))->toContain('Rejected duplicates: 1');
    expect(session('import_errors')[0] ?? '')->toContain('archived beneficiary');
    expect(Beneficiary::withTrashed()->count())->toBe(1);
});
