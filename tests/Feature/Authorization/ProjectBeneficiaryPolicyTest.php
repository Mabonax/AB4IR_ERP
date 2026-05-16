<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\NextOfKin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

function makeProject(): Project
{
    $department = StaffDepartment::query()->create([
        'name' => 'Operations',
        'description' => 'Operations department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Mia',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::random(8).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Business Incubation',
        'description' => 'Core incubation programme',
        'slug' => 'business-incubation-'.Str::lower(Str::random(6)),
    ]);

    return Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Pilot Project',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Pilot delivery project',
    ]);
}

function makeBeneficiary(Project $project): Beneficiary
{
    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Nora',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0711111111',
        'email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    return Beneficiary::query()->create([
        'name' => 'Ava',
        'surname' => 'Beneficiary',
        'dob' => now()->subYears(24),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $project->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);
}

test('project policy allows viewers to read and managers to mutate', function () {
    $project = makeProject();
    $viewer = grantDomainAccess(User::factory()->create(), 'projects', manage: false);
    $manager = grantDomainAccess(User::factory()->create(), 'projects');
    $outsider = User::factory()->create();

    expect(Gate::forUser($viewer)->allows('viewAny', Project::class))->toBeTrue();
    expect(Gate::forUser($viewer)->allows('view', $project))->toBeTrue();
    expect(Gate::forUser($viewer)->allows('create', Project::class))->toBeFalse();
    expect(Gate::forUser($viewer)->allows('update', $project))->toBeFalse();

    expect(Gate::forUser($manager)->allows('create', Project::class))->toBeTrue();
    expect(Gate::forUser($manager)->allows('update', $project))->toBeTrue();
    expect(Gate::forUser($manager)->allows('delete', $project))->toBeTrue();

    expect(Gate::forUser($outsider)->allows('viewAny', Project::class))->toBeFalse();
    expect(Gate::forUser($outsider)->allows('view', $project))->toBeFalse();
});

test('beneficiary policy mirrors view versus manage domain permissions', function () {
    $project = makeProject();
    $beneficiary = makeBeneficiary($project);
    $viewer = grantDomainAccess(User::factory()->create(), 'beneficiaries', manage: false);
    $manager = grantDomainAccess(User::factory()->create(), 'beneficiaries');
    $outsider = User::factory()->create();

    expect(Gate::forUser($viewer)->allows('viewAny', Beneficiary::class))->toBeTrue();
    expect(Gate::forUser($viewer)->allows('view', $beneficiary))->toBeTrue();
    expect(Gate::forUser($viewer)->allows('create', Beneficiary::class))->toBeFalse();
    expect(Gate::forUser($viewer)->allows('update', $beneficiary))->toBeFalse();

    expect(Gate::forUser($manager)->allows('create', Beneficiary::class))->toBeTrue();
    expect(Gate::forUser($manager)->allows('update', $beneficiary))->toBeTrue();
    expect(Gate::forUser($manager)->allows('delete', $beneficiary))->toBeTrue();

    expect(Gate::forUser($outsider)->allows('viewAny', Beneficiary::class))->toBeFalse();
    expect(Gate::forUser($outsider)->allows('view', $beneficiary))->toBeFalse();
});
