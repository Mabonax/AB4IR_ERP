<?php

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectService;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeProjectCommercialGraph(): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Commercial Department '.Str::upper(Str::random(4)),
        'description' => 'Commercial Department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Cleo',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Commercial Program',
        'description' => 'Commercial Program',
        'slug' => 'commercial-program-'.Str::lower(Str::random(5)),
    ]);

    $sponsor = Stakeholder::query()->create([
        'organization_name' => 'Primary Sponsor',
        'name' => 'Sponsor Contact',
        'status' => 'active',
    ]);

    $partnerA = Stakeholder::query()->create([
        'organization_name' => 'Partner Alpha',
        'name' => 'Partner Contact A',
        'status' => 'active',
    ]);

    $partnerB = Stakeholder::query()->create([
        'organization_name' => 'Partner Beta',
        'name' => 'Partner Contact B',
        'status' => 'active',
    ]);

    return compact('manager', 'program', 'sponsor', 'partnerA', 'partnerB');
}

test('project service persists sponsor and implementation partners on create', function () {
    $graph = makeProjectCommercialGraph();

    $project = app(ProjectService::class)->createProject([
        'program_id' => $graph['program']->id,
        'sponsor_stakeholder_id' => $graph['sponsor']->id,
        'partner_stakeholder_ids' => [$graph['partnerA']->id, $graph['partnerB']->id],
        'project_manager_id' => $graph['manager']->id,
        'contract_reference' => 'CTR-001',
        'funding_amount' => 150000,
        'reporting_cadence' => 'monthly',
        'reporting_obligations' => 'Submit monthly sponsor progress and beneficiary updates.',
        'name' => 'Commercial Structure Project',
        'start_date' => now()->toDateString(),
        'end_date' => null,
        'status' => 'planned',
        'description' => 'Commercial structure project',
    ]);

    expect($project->sponsor_stakeholder_id)->toBe($graph['sponsor']->id);
    expect($project->partners->pluck('id')->sort()->values()->all())
        ->toBe([$graph['partnerA']->id, $graph['partnerB']->id]);
    expect($project->contract_reference)->toBe('CTR-001');
    expect((float) $project->funding_amount)->toBe(150000.0);
    expect($project->reporting_cadence)->toBe('monthly');
});

test('project service syncs implementation partners on update', function () {
    $graph = makeProjectCommercialGraph();

    $project = Project::query()->create([
        'program_id' => $graph['program']->id,
        'sponsor_stakeholder_id' => $graph['sponsor']->id,
        'project_manager_id' => $graph['manager']->id,
        'name' => 'Commercial Update Project',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Commercial update project',
    ]);

    $project->partners()->sync([$graph['partnerA']->id]);

    $updated = app(ProjectService::class)->updateProject($project->id, [
        'program_id' => $graph['program']->id,
        'sponsor_stakeholder_id' => $graph['sponsor']->id,
        'partner_stakeholder_ids' => [$graph['partnerB']->id],
        'project_manager_id' => $graph['manager']->id,
        'contract_reference' => 'CTR-002',
        'funding_amount' => 250000,
        'reporting_cadence' => 'quarterly',
        'reporting_obligations' => 'Submit quarterly consolidated reporting pack.',
        'name' => $project->name,
        'start_date' => $project->start_date->format('Y-m-d'),
        'end_date' => null,
        'status' => 'planned',
        'description' => $project->description,
    ]);

    expect($updated->partners->pluck('id')->all())->toBe([$graph['partnerB']->id]);
    expect($updated->contract_reference)->toBe('CTR-002');
    expect((float) $updated->funding_amount)->toBe(250000.0);
    expect($updated->reporting_cadence)->toBe('quarterly');
});
