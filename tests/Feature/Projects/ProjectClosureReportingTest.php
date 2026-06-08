<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Projects\Models\ProjectReport;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\NextOfKin;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeProjectGovernanceGraph(): array
{
    $managerUser = User::factory()->create();

    $department = StaffDepartment::query()->create([
        'name' => 'Governance Department '.Str::upper(Str::random(4)),
        'description' => 'Governance Department',
    ]);

    $manager = StaffMember::query()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'first_name' => 'Gina',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Governance Program',
        'description' => 'Governance Program',
        'slug' => 'governance-program-'.Str::lower(Str::random(5)),
    ]);

    $sponsor = Stakeholder::query()->create([
        'organization_name' => 'Governance Sponsor',
        'name' => 'Sponsor Contact',
        'status' => 'active',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'sponsor_stakeholder_id' => $sponsor->id,
        'project_manager_id' => $manager->id,
        'name' => 'Governance Project',
        'start_date' => '2026-05-01',
        'status' => 'active',
        'description' => 'Governance project',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Governance Province '.Str::upper(Str::random(4)),
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Fiona',
        'surname' => 'Governance',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '1 Governance Street',
        'email' => 'facilitator-'.Str::lower(Str::random(8)).'@example.com',
        'cell' => '0712345678',
        'specialization' => 'Training',
        'province_id' => $province->id,
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Governance Hall',
    ]);

    $template = ProgramMilestoneTemplate::query()->create([
        'program_id' => $program->id,
        'title' => 'Governance Milestone Template',
        'description' => 'Governance Milestone Template',
        'sort_order' => 1,
        'max_score' => 10,
    ]);

    $milestone = ProjectMilestone::query()->create([
        'project_id' => $project->id,
        'program_milestone_template_id' => $template->id,
        'title' => 'Governance Milestone',
        'description' => 'Governance Milestone',
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

    $beneficiary = Beneficiary::query()->create([
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

    return compact('managerUser', 'project', 'location', 'milestone', 'beneficiary', 'enrollment');
}

test('project manager can generate a progress report from the project detail workflow', function () {
    $graph = makeProjectGovernanceGraph();
    grantPermissions($graph['managerUser'], ['domain.projects.view']);

    $response = $this->actingAs($graph['managerUser'])->post(route('projects.reports.store', $graph['project']->id), [
        'report_type' => 'progress',
        'report_date' => '2026-05-18',
        'title' => 'May Progress Report',
        'executive_summary' => 'Delivery is underway.',
        'key_findings' => 'Attendance is still ramping up.',
        'recommendations' => 'Increase monitoring at location level.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Progress report created.');

    $this->assertDatabaseHas('project_reports', [
        'project_id' => $graph['project']->id,
        'report_type' => 'progress',
        'title' => 'May Progress Report',
    ]);
});

test('project manager can conclude a project and an automatic final report is generated', function () {
    $graph = makeProjectGovernanceGraph();
    grantPermissions($graph['managerUser'], ['domain.projects.view']);

    ProjectMilestoneAssessment::query()->create([
        'project_milestone_id' => $graph['milestone']->id,
        'beneficiary_id' => $graph['beneficiary']->id,
        'project_location_id' => $graph['location']->id,
        'facilitator_id' => $graph['location']->facilitator_id,
        'status' => 'completed',
        'score' => 8,
        'comments' => 'Completed',
        'assessed_at' => now(),
    ]);

    $response = $this->actingAs($graph['managerUser'])->post(route('projects.conclude', $graph['project']->id), [
        'closure_date' => '2026-05-18',
        'signoff_notes' => 'All delivery obligations were met.',
        'final_report_summary' => 'Project completed successfully across the active site.',
        'report_title' => 'Governance Project Final Report',
        'key_findings' => 'Milestone completion reached target.',
        'recommendations' => 'Scale the delivery pattern into the next intake.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Project concluded and final report generated.');

    $this->assertDatabaseHas('projects', [
        'id' => $graph['project']->id,
        'status' => 'completed',
        'end_date' => '2026-05-18',
    ]);
    $this->assertDatabaseHas('project_enrollments', [
        'id' => $graph['enrollment']->id,
        'status' => 'completed',
    ]);
    $this->assertDatabaseHas('project_closures', [
        'project_id' => $graph['project']->id,
        'closure_date' => '2026-05-18',
    ]);
    $this->assertDatabaseHas('project_reports', [
        'project_id' => $graph['project']->id,
        'report_type' => 'final',
        'title' => 'Governance Project Final Report',
    ]);
});

test('project report pdf can be downloaded by a project viewer', function () {
    $graph = makeProjectGovernanceGraph();
    grantPermissions($graph['managerUser'], ['domain.projects.view']);

    $report = ProjectReport::query()->create([
        'project_id' => $graph['project']->id,
        'report_type' => 'progress',
        'title' => 'Downloadable Report',
        'report_date' => '2026-05-18',
        'executive_summary' => 'Summary',
        'created_by_user_id' => $graph['managerUser']->id,
        'snapshot' => ['summary' => ['total_locations' => 1], 'locations' => []],
    ]);

    $viewer = grantDomainAccess(User::factory()->create(), 'projects', false);

    $response = $this->actingAs($viewer)->get(route('projects.reports.pdf', [
        'project' => $graph['project']->id,
        'report' => $report->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('project manager can upload closure evidence and it is recorded in project history', function () {
    Storage::fake('local');

    $graph = makeProjectGovernanceGraph();
    grantPermissions($graph['managerUser'], ['domain.projects.view']);

    $response = $this->actingAs($graph['managerUser'])->post(route('projects.closure-evidence.store', $graph['project']->id), [
        'category' => 'registers',
        'title' => 'Attendance Export',
        'notes' => 'Final attendance evidence.',
        'file' => UploadedFile::fake()->create('attendance-export.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Project evidence uploaded.');

    $this->assertDatabaseHas('project_closure_evidence', [
        'project_id' => $graph['project']->id,
        'category' => 'registers',
        'title' => 'Attendance Export',
    ]);
    $this->assertDatabaseHas('project_history', [
        'project_id' => $graph['project']->id,
        'action' => 'closure_evidence_uploaded',
    ]);
});
