<?php

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Services\ProjectAccessService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeAccessGraph(): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Access Department',
        'description' => 'Access Department',
    ]);

    $managerUser = User::factory()->create();
    $manager = StaffMember::query()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'first_name' => 'Pat',
        'last_name' => 'Manager',
        'email' => 'staff-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Access Program',
        'description' => 'Access Program',
        'slug' => 'access-program-'.Str::lower(Str::random(4)),
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Access Project',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Project for access tests',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Access Province '.Str::upper(Str::random(4)),
    ]);

    $facilitatorUser = User::factory()->create();
    $facilitator = Facilitator::query()->create([
        'user_id' => $facilitatorUser->id,
        'name' => 'Fae',
        'surname' => 'Cilitator',
        'dob' => now()->subYears(29)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '12 Access Street',
        'email' => 'facilitator-'.Str::lower(Str::random(8)).'@example.com',
        'cell' => '0712345678',
        'specialization' => 'Training',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Access Hall',
    ]);

    return compact('managerUser', 'facilitatorUser', 'project', 'location');
}

test('project access service grants assigned facilitator location access', function () {
    $graph = makeAccessGraph();
    $this->actingAs($graph['facilitatorUser']);

    $service = app(ProjectAccessService::class);

    expect($service->canAccessAssignedLocation($graph['location']))->toBeTrue();
    expect($service->currentFacilitator()?->id)->toBe($graph['location']->facilitator_id);
});

test('project access service grants project summary access to the project manager', function () {
    $graph = makeAccessGraph();
    $this->actingAs($graph['managerUser']);

    $service = app(ProjectAccessService::class);

    expect($service->isProjectManager($graph['project']))->toBeTrue();
});
