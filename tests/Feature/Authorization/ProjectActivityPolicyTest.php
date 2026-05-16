<?php

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeProjectActivityGraph(string $projectStatus = 'active'): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Policy Department '.Str::upper(Str::random(4)),
        'description' => 'Policy Department',
    ]);

    $managerUser = User::factory()->create();
    $manager = StaffMember::query()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'first_name' => 'Pia',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Policy Program',
        'description' => 'Policy Program',
        'slug' => 'policy-program-'.Str::lower(Str::random(5)),
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Policy Project',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'status' => $projectStatus,
        'description' => 'Policy project',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Policy Province '.Str::upper(Str::random(4)),
    ]);

    $facilitatorUser = User::factory()->create();
    $facilitator = Facilitator::query()->create([
        'user_id' => $facilitatorUser->id,
        'name' => 'Fiona',
        'surname' => 'Trainer',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '1 Policy Street',
        'email' => 'facilitator-'.Str::lower(Str::random(8)).'@example.com',
        'cell' => '0712345678',
        'specialization' => 'Training',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Policy Hall',
    ]);

    $register = AttendanceRegister::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'facilitator_id' => $facilitator->id,
        'attendance_date' => now()->toDateString(),
        'is_holiday' => false,
    ]);

    $projectManager = grantDomainAccess(User::factory()->create(), 'projects');
    $registerViewer = grantDomainAccess(User::factory()->create(), 'projects', manage: false);

    return compact('project', 'location', 'register', 'managerUser', 'facilitatorUser', 'projectManager', 'registerViewer');
}

test('assigned facilitator can view and manage attendance plus assessments for active project locations', function () {
    $graph = makeProjectActivityGraph('active');

    expect(Gate::forUser($graph['facilitatorUser'])->allows('viewLocation', [AttendanceRegister::class, $graph['location']]))->toBeTrue();
    expect(Gate::forUser($graph['facilitatorUser'])->allows('manageLocation', [AttendanceRegister::class, $graph['location']]))->toBeTrue();
    expect(Gate::forUser($graph['facilitatorUser'])->allows('markHoliday', [AttendanceRegister::class, $graph['location']]))->toBeFalse();
    expect(Gate::forUser($graph['facilitatorUser'])->allows('store', [ProjectMilestoneAssessment::class, $graph['location']]))->toBeTrue();
});

test('project manager can view attendance context and mark holidays but not capture registers', function () {
    $graph = makeProjectActivityGraph('active');

    expect(Gate::forUser($graph['managerUser'])->allows('viewLocation', [AttendanceRegister::class, $graph['location']]))->toBeTrue();
    expect(Gate::forUser($graph['managerUser'])->allows('markHoliday', [AttendanceRegister::class, $graph['location']]))->toBeTrue();
    expect(Gate::forUser($graph['managerUser'])->allows('manageLocation', [AttendanceRegister::class, $graph['location']]))->toBeFalse();
    expect(Gate::forUser($graph['managerUser'])->allows('viewAttendanceSummary', $graph['project']))->toBeTrue();
});

test('operational mutations are blocked once a project is no longer active', function () {
    $graph = makeProjectActivityGraph('completed');

    expect(Gate::forUser($graph['facilitatorUser'])->allows('manageLocation', [AttendanceRegister::class, $graph['location']]))->toBeFalse();
    expect(Gate::forUser($graph['managerUser'])->allows('markHoliday', [AttendanceRegister::class, $graph['location']]))->toBeFalse();
    expect(Gate::forUser($graph['projectManager'])->allows('store', [ProjectMilestoneAssessment::class, $graph['location']]))->toBeFalse();
});

test('project viewers can inspect attendance but cannot run workflow mutations', function () {
    $graph = makeProjectActivityGraph('active');

    expect(Gate::forUser($graph['registerViewer'])->allows('viewLocation', [AttendanceRegister::class, $graph['location']]))->toBeTrue();
    expect(Gate::forUser($graph['registerViewer'])->allows('export', $graph['register']->fresh('location.project', 'location.facilitator')))->toBeTrue();
    expect(Gate::forUser($graph['registerViewer'])->allows('manageLocation', [AttendanceRegister::class, $graph['location']]))->toBeFalse();
    expect(Gate::forUser($graph['registerViewer'])->allows('store', [ProjectMilestoneAssessment::class, $graph['location']]))->toBeFalse();
});
