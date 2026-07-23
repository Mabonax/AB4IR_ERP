<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\NextOfKin;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeBeneficiaryLifecycleGraph(string $projectName = 'Youth Cohort 2026', string $provinceName = 'Gauteng'): array
{
    $department = StaffDepartment::query()->create([
        'name' => 'Delivery '.Str::upper(Str::random(4)),
        'description' => 'Delivery department',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Project',
        'last_name' => 'Manager',
        'email' => 'manager-'.Str::lower(Str::random(8)).'@example.com',
        'employee_number' => 'EMP-'.Str::upper(Str::random(8)),
        'status' => 'active',
        'is_manager' => true,
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
        'name' => 'Life',
        'surname' => 'Coach',
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

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Nora',
        'surname' => 'Kin',
        'relationship' => 'Sibling',
        'phone' => '0733333333',
        'email' => 'nok-'.Str::lower(Str::random(8)).'@example.com',
    ]);

    $beneficiary = Beneficiary::query()->create([
        'name' => 'Lebo',
        'surname' => 'Mokoena',
        'dob' => now()->subYears(24),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'beneficiary-'.Str::lower(Str::random(8)).'@example.com',
        'phone' => '0722222222',
        'gender' => 'female',
        'project_id' => $project->id,
        'attendance_status' => 'active',
        'status' => 'enrolled',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => 'enrolled',
        'enrolled_at' => now()->subWeek(),
    ]);

    return compact('program', 'project', 'location', 'beneficiary');
}

test('beneficiary lifecycle routes suspend and reinstate with history and notifications', function () {
    $graph = makeBeneficiaryLifecycleGraph();
    $actor = grantDomainAccess(User::factory()->create(), 'beneficiaries');
    $recipient = grantDomainAccess(User::factory()->create(), 'beneficiaries');

    $this->actingAs($actor)
        ->post(route('beneficiaries.suspend', $graph['beneficiary']->id), [
            'reason' => 'Attendance paused pending case review.',
        ])
        ->assertRedirect(route('beneficiaries.show', $graph['beneficiary']->id));

    $this->assertDatabaseHas('beneficiaries', [
        'id' => $graph['beneficiary']->id,
        'status' => 'suspended',
        'status_reason' => 'Attendance paused pending case review.',
    ]);

    $this->assertDatabaseHas('beneficiary_history', [
        'beneficiary_id' => $graph['beneficiary']->id,
        'action' => 'suspended',
        'to_status' => 'suspended',
    ]);

    expect($recipient->fresh()->notifications()->count())->toBe(1);

    $this->actingAs($actor)
        ->post(route('beneficiaries.reinstate', $graph['beneficiary']->id), [
            'reason' => 'Review complete and placement can continue.',
        ])
        ->assertRedirect(route('beneficiaries.show', $graph['beneficiary']->id));

    $this->assertDatabaseHas('beneficiaries', [
        'id' => $graph['beneficiary']->id,
        'status' => 'enrolled',
    ]);

    $this->assertDatabaseHas('beneficiary_history', [
        'beneficiary_id' => $graph['beneficiary']->id,
        'action' => 'reinstated',
        'to_status' => 'enrolled',
    ]);
});

test('beneficiary lifecycle routes graduate and surface cohort metrics on the directory', function () {
    $graph = makeBeneficiaryLifecycleGraph();
    $actor = grantDomainAccess(User::factory()->create(), 'beneficiaries');

    $this->actingAs($actor)
        ->post(route('beneficiaries.graduate', $graph['beneficiary']->id), [
            'reason' => 'Training and placement targets completed.',
            'outcome_type' => 'employment',
            'outcome_notes' => 'Placed with a local studio.',
        ])
        ->assertRedirect(route('beneficiaries.show', $graph['beneficiary']->id));

    $this->assertDatabaseHas('beneficiaries', [
        'id' => $graph['beneficiary']->id,
        'status' => 'graduated',
    ]);

    $this->assertDatabaseHas('beneficiary_outcomes', [
        'beneficiary_id' => $graph['beneficiary']->id,
        'project_id' => $graph['project']->id,
        'outcome_type' => 'employment',
    ]);

    $this->actingAs($actor)
        ->get('/beneficiaries?program_id='.$graph['program']->id.'&project_id='.$graph['project']->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Beneficiaries/Index')
            ->where('lifecycleMetrics.graduated_beneficiaries', 1)
            ->where('lifecycleMetrics.employment_outcomes', 1)
            ->where('beneficiary.data.0.status', 'graduated')
        );
});

test('beneficiary transfer updates placement, preserves history, and marks prior enrollment dropped', function () {
    $origin = makeBeneficiaryLifecycleGraph('Alpha Cohort 2026', 'Limpopo');
    $target = makeBeneficiaryLifecycleGraph('Beta Cohort 2026', 'Mpumalanga');
    $actor = grantDomainAccess(User::factory()->create(), 'beneficiaries');

    $this->actingAs($actor)
        ->post(route('beneficiaries.transfer', $origin['beneficiary']->id), [
            'reason' => 'Moved to a closer delivery site.',
            'project_id' => $target['project']->id,
            'project_location_id' => $target['location']->id,
        ])
        ->assertRedirect(route('beneficiaries.show', $origin['beneficiary']->id));

    $this->assertDatabaseHas('beneficiaries', [
        'id' => $origin['beneficiary']->id,
        'project_id' => $target['project']->id,
        'status' => 'enrolled',
    ]);

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $origin['project']->id,
        'beneficiary_id' => $origin['beneficiary']->id,
        'status' => 'dropped',
    ]);

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $target['project']->id,
        'project_location_id' => $target['location']->id,
        'beneficiary_id' => $origin['beneficiary']->id,
        'status' => 'enrolled',
    ]);

    $this->assertDatabaseHas('beneficiary_history', [
        'beneficiary_id' => $origin['beneficiary']->id,
        'action' => 'transferred',
    ]);
});

test('beneficiary exit and archive routes record lifecycle outcomes and remove archived records from active queries', function () {
    $graph = makeBeneficiaryLifecycleGraph();
    $actor = grantDomainAccess(User::factory()->create(), 'beneficiaries');

    $this->actingAs($actor)
        ->post(route('beneficiaries.exit', $graph['beneficiary']->id), [
            'reason' => 'Beneficiary withdrew from the programme.',
            'outcome_type' => 'unknown_outcome',
        ])
        ->assertRedirect(route('beneficiaries.show', $graph['beneficiary']->id));

    $this->assertDatabaseHas('beneficiaries', [
        'id' => $graph['beneficiary']->id,
        'status' => 'exited',
        'exit_reason' => 'Beneficiary withdrew from the programme.',
    ]);

    $this->actingAs($actor)
        ->post(route('beneficiaries.archive', $graph['beneficiary']->id), [
            'reason' => 'Record closed after exit review.',
        ])
        ->assertRedirect(route('beneficiaries.index'));

    $this->assertSoftDeleted('beneficiaries', [
        'id' => $graph['beneficiary']->id,
        'status' => 'archived',
    ]);
});

test('beneficiary lifecycle routes require manage permission', function () {
    $graph = makeBeneficiaryLifecycleGraph();
    $viewer = grantDomainAccess(User::factory()->create(), 'beneficiaries', manage: false);

    $this->actingAs($viewer)
        ->post(route('beneficiaries.suspend', $graph['beneficiary']->id), [
            'reason' => 'Unauthorized attempt.',
        ])
        ->assertForbidden();
});
