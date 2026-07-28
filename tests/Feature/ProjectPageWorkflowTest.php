<?php

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeProjectPageWorkflowFixture(): array
{
    $department = StaffDepartment::query()->create([
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
        'title' => 'Project Workflow Program',
        'description' => 'Program for project page workflow tests',
        'slug' => 'project-workflow-program-'.Str::lower(Str::random(5)),
    ]);

    $stakeholder = Stakeholder::query()->create([
        'organization_name' => 'POA Partner '.Str::upper(Str::random(4)),
        'name' => 'Primary Contact',
        'email' => 'stakeholder-'.Str::lower(Str::random(8)).'@example.com',
        'contact_number' => '010'.random_int(1000000, 9999999),
        'status' => 'active',
    ]);

    $partnerStakeholder = Stakeholder::query()->create([
        'organization_name' => 'POA Delivery '.Str::upper(Str::random(4)),
        'name' => 'Delivery Partner',
        'email' => 'partner-'.Str::lower(Str::random(8)).'@example.com',
        'contact_number' => '011'.random_int(1000000, 9999999),
        'status' => 'active',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'sponsor_stakeholder_id' => $stakeholder->id,
        'name' => 'Cohort Delivery 2026',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Workflow test cohort',
        'contract_reference' => 'POA-TEST-2026',
        'funding_amount' => 250000,
        'reporting_cadence' => 'monthly',
        'reporting_obligations' => 'Monthly operational reporting',
    ]);

    $project->partners()->sync([$partnerStakeholder->id]);

    return compact('program', 'manager', 'stakeholder', 'partnerStakeholder', 'project');
}

test('authorized user can open the project create page', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'projects');
    $fixture = makeProjectPageWorkflowFixture();

    $this->actingAs($user)
        ->get('/projects/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Create')
            ->has('programs', 1)
            ->where('programs.0.id', $fixture['program']->id)
            ->has('staffMembers', 1)
            ->has('stakeholders', 2)
        );
});

test('authorized user can open the project edit page', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'projects');
    $fixture = makeProjectPageWorkflowFixture();

    $this->actingAs($user)
        ->get("/projects/{$fixture['project']->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Edit')
            ->where('project.data.id', $fixture['project']->id)
            ->where('project.data.name', 'Cohort Delivery 2026')
        );
});

test('authorized user can open the project finalization page from the project route space', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'projects');
    $fixture = makeProjectPageWorkflowFixture();

    $this->actingAs($user)
        ->get("/projects/{$fixture['project']->id}/finalization")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Finalization')
            ->where('project.data.id', $fixture['project']->id)
            ->where('project.data.name', 'Cohort Delivery 2026')
            ->has('closureEvidence', 0)
            ->has('reports', 0)
        );
});

test('project show page exposes finalization entrypoint instead of embedded governance workflow props', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'projects');
    $fixture = makeProjectPageWorkflowFixture();

    $this->actingAs($user)
        ->get("/projects/{$fixture['project']->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('project.data.id', $fixture['project']->id)
            ->has('finalization')
            ->where('finalization.href', route('projects.finalization', $fixture['project']->id))
            ->missing('closure')
            ->missing('closureEvidence')
            ->missing('reports')
            ->missing('canManageGovernance')
        );
});

test('creating a project from the page redirects to the project file', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'projects');
    $fixture = makeProjectPageWorkflowFixture();

    $response = $this->actingAs($user)->post('/projects', [
        'program_id' => $fixture['program']->id,
        'project_manager_id' => $fixture['manager']->id,
        'sponsor_stakeholder_id' => $fixture['stakeholder']->id,
        'partner_stakeholder_ids' => [$fixture['partnerStakeholder']->id],
        'name' => 'Expanded Delivery 2027',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(6)->toDateString(),
        'status' => 'planned',
        'description' => 'Expanded delivery project',
        'contract_reference' => 'POA-EXP-2027',
        'funding_amount' => 500000,
        'reporting_cadence' => 'quarterly',
        'reporting_obligations' => 'Quarterly reporting',
    ]);

    $project = Project::query()->where('name', 'Expanded Delivery 2027')->firstOrFail();

    $response->assertRedirect("/projects/{$project->id}");
});

test('creating a project from the page allows deferring optional governance and manager fields', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'projects');
    $fixture = makeProjectPageWorkflowFixture();

    $response = $this->actingAs($user)->post('/projects', [
        'program_id' => $fixture['program']->id,
        'name' => 'Lean Intake Project',
        'start_date' => now()->toDateString(),
        'description' => 'Created with only the minimum required delivery details.',
    ]);

    $project = Project::query()->where('name', 'Lean Intake Project')->firstOrFail();

    $response->assertRedirect("/projects/{$project->id}");
    expect($project->project_manager_id)->toBeNull();
    expect($project->status)->toBe('planned');
});
