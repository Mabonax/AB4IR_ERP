<?php

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Projects\Services\ProjectService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\NextOfKin;
use App\Models\Provinces;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function makeProgressionGraph(): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Progression Department '.Str::upper(Str::random(4)),
        'description' => 'Progression Department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Pia',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Progression Program',
        'description' => 'Progression Program',
        'slug' => 'progression-program-'.Str::lower(Str::random(5)),
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Progression Project',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Progression project',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Progression Province '.Str::upper(Str::random(4)),
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Fiona',
        'surname' => 'Trainer',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '1 Progression Street',
        'email' => 'facilitator-'.Str::lower(Str::random(8)).'@example.com',
        'cell' => '0712345678',
        'specialization' => 'Training',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Progress Hall',
    ]);

    $template = ProgramMilestoneTemplate::query()->create([
        'program_id' => $program->id,
        'title' => 'Milestone Template',
        'description' => 'Milestone Template',
        'sort_order' => 1,
        'max_score' => 10,
    ]);

    $milestone = ProjectMilestone::query()->create([
        'project_id' => $project->id,
        'program_milestone_template_id' => $template->id,
        'title' => 'Milestone 1',
        'description' => 'Milestone 1',
        'sort_order' => 1,
        'max_score' => 10,
    ]);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Nora',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0710000000',
        'email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $beneficiary = \App\Domains\Beneficiaries\Models\Beneficiary::query()->create([
        'name' => 'Lebo',
        'surname' => 'Participant',
        'dob' => now()->subYears(21),
        'age' => 21,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $project->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    $enrollment = ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    return compact('project', 'location', 'milestone', 'beneficiary', 'enrollment');
}

test('project cannot move from planned to active without locations and milestones', function () {
    $department = StaffDepartment::query()->create([
        'name' => 'Bare Department',
        'description' => 'Bare Department',
    ]);
    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'No',
        'last_name' => 'Setup',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);
    $program = Program::query()->create([
        'title' => 'Bare Program',
        'description' => 'Bare Program',
        'slug' => 'bare-program-'.Str::lower(Str::random(5)),
    ]);
    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Bare Project',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Bare project',
    ]);

    $service = app(ProjectService::class);

    expect(fn () => $service->updateProject($project->id, [
        'program_id' => $project->program_id,
        'project_manager_id' => $project->project_manager_id,
        'name' => $project->name,
        'start_date' => $project->start_date->format('Y-m-d'),
        'end_date' => null,
        'status' => 'active',
        'description' => $project->description,
    ]))->toThrow(ValidationException::class);
});

test('project creation cannot start directly as active before operational setup exists', function () {
    $department = StaffDepartment::query()->create([
        'name' => 'Create Department',
        'description' => 'Create Department',
    ]);
    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Ari',
        'last_name' => 'Starter',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);
    $program = Program::query()->create([
        'title' => 'Create Program',
        'description' => 'Create Program',
        'slug' => 'create-program-'.Str::lower(Str::random(5)),
    ]);

    $service = app(ProjectService::class);

    expect(fn () => $service->createProject([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Active On Create',
        'start_date' => now()->toDateString(),
        'end_date' => null,
        'status' => 'active',
        'description' => 'Should fail readiness on create',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('projects', [
        'name' => 'Active On Create',
    ]);
});

test('project creation cannot start directly as completed before delivery evidence exists', function () {
    $department = StaffDepartment::query()->create([
        'name' => 'Complete Department',
        'description' => 'Complete Department',
    ]);
    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Cora',
        'last_name' => 'Closer',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);
    $program = Program::query()->create([
        'title' => 'Completion Program',
        'description' => 'Completion Program',
        'slug' => 'completion-program-'.Str::lower(Str::random(5)),
    ]);

    $service = app(ProjectService::class);

    expect(fn () => $service->createProject([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Completed On Create',
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'status' => 'completed',
        'description' => 'Should fail completion readiness on create',
    ]))->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('projects', [
        'name' => 'Completed On Create',
    ]);
});

test('project cannot be completed until all active beneficiaries complete all milestones', function () {
    $graph = makeProgressionGraph();
    $service = app(ProjectService::class);

    expect(fn () => $service->updateProject($graph['project']->id, [
        'program_id' => $graph['project']->program_id,
        'project_manager_id' => $graph['project']->project_manager_id,
        'name' => $graph['project']->name,
        'start_date' => $graph['project']->start_date->format('Y-m-d'),
        'end_date' => now()->toDateString(),
        'status' => 'completed',
        'description' => $graph['project']->description,
    ]))->toThrow(ValidationException::class);
});

test('project completion marks active enrollments completed after milestone delivery is complete', function () {
    $graph = makeProgressionGraph();
    $service = app(ProjectService::class);

    ProjectMilestoneAssessment::query()->create([
        'project_milestone_id' => $graph['milestone']->id,
        'beneficiary_id' => $graph['beneficiary']->id,
        'project_location_id' => $graph['location']->id,
        'status' => 'completed',
        'score' => 8,
        'comments' => 'Completed',
        'assessed_at' => now(),
    ]);

    $updated = $service->updateProject($graph['project']->id, [
        'program_id' => $graph['project']->program_id,
        'project_manager_id' => $graph['project']->project_manager_id,
        'name' => $graph['project']->name,
        'start_date' => $graph['project']->start_date->format('Y-m-d'),
        'end_date' => now()->toDateString(),
        'status' => 'completed',
        'description' => $graph['project']->description,
    ]);

    expect($updated->status)->toBe('completed');
    $this->assertDatabaseHas('project_enrollments', [
        'id' => $graph['enrollment']->id,
        'status' => 'completed',
    ]);
});

test('dropped enrollments do not block project completion once active beneficiaries finish delivery', function () {
    $graph = makeProgressionGraph();
    $service = app(ProjectService::class);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Drew',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0711111111',
        'email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $droppedBeneficiary = \App\Domains\Beneficiaries\Models\Beneficiary::query()->create([
        'name' => 'Drop',
        'surname' => 'Out',
        'dob' => now()->subYears(20),
        'age' => 20,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0723333333',
        'gender' => 'male',
        'project_id' => $graph['project']->id,
        'attendance_status' => 'dropout',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $graph['project']->id,
        'project_location_id' => $graph['location']->id,
        'beneficiary_id' => $droppedBeneficiary->id,
        'status' => 'dropped',
        'enrolled_at' => now(),
    ]);

    ProjectMilestoneAssessment::query()->create([
        'project_milestone_id' => $graph['milestone']->id,
        'beneficiary_id' => $graph['beneficiary']->id,
        'project_location_id' => $graph['location']->id,
        'status' => 'completed',
        'score' => 8,
        'comments' => 'Completed',
        'assessed_at' => now(),
    ]);

    $updated = $service->updateProject($graph['project']->id, [
        'program_id' => $graph['project']->program_id,
        'project_manager_id' => $graph['project']->project_manager_id,
        'name' => $graph['project']->name,
        'start_date' => $graph['project']->start_date->format('Y-m-d'),
        'end_date' => now()->toDateString(),
        'status' => 'completed',
        'description' => $graph['project']->description,
    ]);

    expect($updated->status)->toBe('completed');
    $this->assertDatabaseHas('project_enrollments', [
        'beneficiary_id' => $droppedBeneficiary->id,
        'status' => 'dropped',
    ]);
});

test('completed projects cannot be reopened to active', function () {
    $graph = makeProgressionGraph();
    $service = app(ProjectService::class);

    ProjectMilestoneAssessment::query()->create([
        'project_milestone_id' => $graph['milestone']->id,
        'beneficiary_id' => $graph['beneficiary']->id,
        'project_location_id' => $graph['location']->id,
        'status' => 'completed',
        'score' => 8,
        'comments' => 'Completed',
        'assessed_at' => now(),
    ]);

    $service->updateProject($graph['project']->id, [
        'program_id' => $graph['project']->program_id,
        'project_manager_id' => $graph['project']->project_manager_id,
        'name' => $graph['project']->name,
        'start_date' => $graph['project']->start_date->format('Y-m-d'),
        'end_date' => now()->toDateString(),
        'status' => 'completed',
        'description' => $graph['project']->description,
    ]);

    expect(fn () => $service->updateProject($graph['project']->id, [
        'program_id' => $graph['project']->program_id,
        'project_manager_id' => $graph['project']->project_manager_id,
        'name' => $graph['project']->name,
        'start_date' => $graph['project']->start_date->format('Y-m-d'),
        'end_date' => now()->toDateString(),
        'status' => 'active',
        'description' => $graph['project']->description,
    ]))->toThrow(ValidationException::class);
});

test('project status summary exposes readiness blockers for active and completed transitions', function () {
    $department = StaffDepartment::query()->create([
        'name' => 'Summary Department',
        'description' => 'Summary Department',
    ]);
    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Sia',
        'last_name' => 'Summary',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);
    $program = Program::query()->create([
        'title' => 'Summary Program',
        'description' => 'Summary Program',
        'slug' => 'summary-program-'.Str::lower(Str::random(5)),
    ]);
    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Summary Project',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Summary project',
    ]);

    $summary = app(ProjectService::class)->getStatusSummary($project);

    expect($summary['current'])->toBe('planned');
    expect($summary['readiness']['active']['ready'])->toBeFalse();
    expect($summary['readiness']['completed']['ready'])->toBeFalse();
    expect($summary['readiness']['active']['blockers'])->toContain('A project needs at least one location before it can become active.');
    expect($summary['readiness']['completed']['blockers'])->toContain('A completed project must have an end date.');
});

test('project status summary marks completion ready when every active beneficiary has completed delivery', function () {
    $graph = makeProgressionGraph();

    ProjectMilestoneAssessment::query()->create([
        'project_milestone_id' => $graph['milestone']->id,
        'beneficiary_id' => $graph['beneficiary']->id,
        'project_location_id' => $graph['location']->id,
        'status' => 'completed',
        'score' => 9,
        'comments' => 'Completed',
        'assessed_at' => now(),
    ]);

    $graph['project']->update([
        'end_date' => now()->toDateString(),
    ]);

    $summary = app(ProjectService::class)->getStatusSummary($graph['project']->fresh());

    expect($summary['readiness']['active']['ready'])->toBeTrue();
    expect($summary['readiness']['completed']['ready'])->toBeTrue();
    expect(collect($summary['allowed_transitions'])->firstWhere('status', 'completed')['ready'])->toBeTrue();
});
