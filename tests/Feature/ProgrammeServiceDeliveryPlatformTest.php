<?php

use App\Domains\Committees\Models\Committee;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Geography\Models\Branch;
use App\Domains\Geography\Models\Municipality;
use App\Domains\Geography\Models\Region;
use App\Domains\Geography\Models\Township;
use App\Domains\Geography\Models\Ward;
use App\Domains\Members\Models\Member;
use App\Domains\Organisation\Models\Organisation;
use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Models\ProgrammeOutcome;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectActivity;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\ServiceDelivery\Models\BeneficiaryPlacement;
use App\Domains\ServiceDelivery\Models\ServiceAttendance;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makePhaseFourFixture(): array
{
    $organisation = Organisation::query()->create([
        'name' => 'Program of Action '.Str::upper(Str::random(4)),
        'registration_number' => 'ORG-'.Str::upper(Str::random(8)),
        'organisation_type' => 'Nonprofit',
        'status' => 'active',
    ]);

    $committee = Committee::query()->create([
        'organisation_id' => $organisation->id,
        'name' => 'Service Delivery Committee '.Str::upper(Str::random(3)),
        'status' => 'active',
    ]);

    $department = StaffDepartment::query()->create([
        'name' => 'Programme Operations '.Str::upper(Str::random(3)),
        'description' => 'Programme operations',
    ]);

    $programmeManager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Programme',
        'last_name' => 'Manager',
        'email' => 'programme-manager-'.Str::lower(Str::random(6)).'@example.test',
        'employee_number' => 'EMP-'.Str::upper(Str::random(6)),
        'status' => 'active',
    ]);

    $projectManager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Project',
        'last_name' => 'Manager',
        'email' => 'project-manager-'.Str::lower(Str::random(6)).'@example.test',
        'employee_number' => 'EMP-'.Str::upper(Str::random(6)),
        'status' => 'active',
    ]);

    $province = Provinces::query()->create([
        'name' => 'Gauteng '.Str::upper(Str::random(4)),
    ]);

    $municipality = Municipality::query()->create([
        'province_id' => $province->id,
        'name' => 'City of Johannesburg '.Str::upper(Str::random(3)),
        'code' => 'JHB-'.Str::upper(Str::random(3)),
    ]);

    $region = Region::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'name' => 'Region F '.Str::upper(Str::random(3)),
        'code' => 'RF-'.Str::upper(Str::random(3)),
    ]);

    $township = Township::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'name' => 'Soweto '.Str::upper(Str::random(3)),
    ]);

    $ward = Ward::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'township_id' => $township->id,
        'name' => 'Ward 10 '.Str::upper(Str::random(3)),
        'code' => 'W10-'.Str::upper(Str::random(3)),
    ]);

    $branch = Branch::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'township_id' => $township->id,
        'ward_id' => $ward->id,
        'name' => 'POA Branch '.Str::upper(Str::random(3)),
        'code' => 'BR-'.Str::upper(Str::random(3)),
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Facilitator',
        'surname' => 'One',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '12 Delivery Street',
        'email' => 'facilitator-'.Str::lower(Str::random(6)).'@example.test',
        'cell' => '071'.random_int(1000000, 9999999),
        'specialization' => 'Training',
        'province_id' => $province->id,
    ]);

    $member = Member::query()->create([
        'first_name' => 'Linked',
        'last_name' => 'Member',
        'id_number' => fake()->unique()->numerify('#############'),
        'date_of_birth' => '2001-02-03',
        'gender' => 'female',
        'phone' => '072'.random_int(1000000, 9999999),
        'email' => 'member-'.Str::lower(Str::random(6)).'@example.test',
        'physical_address' => '99 Township Road',
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'township_id' => $township->id,
        'ward_id' => $ward->id,
        'branch_id' => $branch->id,
        'member_type' => 'Beneficiary',
        'status' => 'active',
    ]);

    return compact(
        'organisation',
        'committee',
        'department',
        'programmeManager',
        'projectManager',
        'province',
        'municipality',
        'region',
        'township',
        'ward',
        'branch',
        'facilitator',
        'member',
    );
}

function phaseFourUser(array $permissions): User
{
    $user = User::factory()->create();
    grantPermissions($user, $permissions);

    return $user;
}

test('programme lifecycle stores phase four programme metadata', function () {
    $fixture = makePhaseFourFixture();
    $user = phaseFourUser(['domain.programs.view', 'domain.programs.manage']);

    $create = $this->actingAs($user)->post('/programs', [
        'title' => 'Digital Skills Programme',
        'code' => 'DSP-2026',
        'description' => 'Digital skills acceleration programme',
        'strategic_objective' => 'Scale youth digital employment readiness.',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'active',
        'budget' => 1500000,
        'funding_source' => 'SETA Grant',
        'responsible_committee_id' => $fixture['committee']->id,
        'programme_manager_id' => $fixture['programmeManager']->id,
        'slug' => 'digital-skills-programme',
    ]);

    $program = Program::query()->where('code', 'DSP-2026')->firstOrFail();

    $create->assertRedirect();
    expect($program->responsible_committee_id)->toBe($fixture['committee']->id);
    expect($program->programme_manager_id)->toBe($fixture['programmeManager']->id);
    expect((float) $program->budget)->toBe(1500000.0);

    $this->actingAs($user)->put("/programs/{$program->id}", [
        'title' => 'Digital Skills Programme',
        'code' => 'DSP-2026',
        'description' => 'Digital skills acceleration programme',
        'strategic_objective' => 'Expand township-based digital training.',
        'start_date' => '2026-01-01',
        'end_date' => '2027-03-31',
        'status' => 'completed',
        'budget' => 1750000,
        'funding_source' => 'SETA Grant',
        'responsible_committee_id' => $fixture['committee']->id,
        'programme_manager_id' => $fixture['programmeManager']->id,
        'slug' => 'digital-skills-programme',
    ])->assertRedirect();

    $program->refresh();

    expect($program->status)->toBe('completed');
    expect($program->strategic_objective)->toBe('Expand township-based digital training.');
});

test('project lifecycle stores programme delivery metadata', function () {
    $fixture = makePhaseFourFixture();
    $user = phaseFourUser(['domain.programs.view', 'domain.programs.manage', 'domain.projects.view', 'domain.projects.manage']);

    $program = Program::query()->create([
        'title' => 'Youth Employment Programme',
        'code' => 'YEP-001',
        'description' => 'Youth employment pipeline',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->post('/projects', [
        'program_id' => $program->id,
        'project_manager_id' => $fixture['projectManager']->id,
        'name' => 'Web Development Bootcamp',
        'project_code' => 'BOOT-001',
        'primary_location' => 'Soweto Campus',
        'start_date' => '2026-02-01',
        'end_date' => '2026-08-31',
        'status' => 'planned',
        'description' => 'Twelve-week web development bootcamp.',
        'budget' => 450000,
        'target_beneficiaries' => 120,
        'contract_reference' => 'BOOT/2026/01',
        'funding_amount' => 500000,
        'reporting_cadence' => 'monthly',
        'reporting_obligations' => 'Monthly sponsor report',
    ]);

    $project = Project::query()->where('project_code', 'BOOT-001')->firstOrFail();

    $response->assertRedirect("/projects/{$project->id}");
    expect($project->primary_location)->toBe('Soweto Campus');
    expect($project->target_beneficiaries)->toBe(120);
    expect((float) $project->budget)->toBe(450000.0);
});

test('beneficiary registration links service-delivery beneficiary to an existing member profile', function () {
    $fixture = makePhaseFourFixture();
    $user = phaseFourUser(['domain.beneficiaries.view', 'domain.beneficiaries.manage']);

    $program = Program::query()->create([
        'title' => 'Women Empowerment Programme',
        'code' => 'WEP-001',
        'description' => 'Women empowerment programme',
        'status' => 'active',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $fixture['projectManager']->id,
        'name' => 'Entrepreneurship Training Cohort',
        'project_code' => 'ETC-001',
        'primary_location' => 'Branch Hub',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Entrepreneurship support cohort',
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $fixture['facilitator']->id,
        'province_id' => $fixture['province']->id,
        'training_venue_address' => 'Branch Hall',
    ]);

    $response = $this->actingAs($user)->post('/beneficiaries', [
        'member_id' => $fixture['member']->id,
        'name' => 'Linked',
        'surname' => 'Member',
        'dob' => '2001-02-03',
        'age' => 25,
        'id_number' => $fixture['member']->id_number,
        'email' => $fixture['member']->email,
        'phone' => $fixture['member']->phone,
        'gender' => 'female',
        'program_id' => $program->id,
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'enrolment_date' => '2026-03-01',
        'participation_status' => 'active',
        'placement_status' => 'pending',
        'member_type' => 'Beneficiary',
        'street_address' => '99 Township Road',
        'city' => 'Johannesburg',
        'province_id' => $fixture['province']->id,
        'postal_code' => '1800',
        'highest_qualification' => 'Diploma',
        'attendance_status' => 'active',
    ]);

    $beneficiary = \App\Domains\Beneficiaries\Models\Beneficiary::query()->latest('id')->firstOrFail();

    $response->assertRedirect("/beneficiaries/{$beneficiary->id}");
    expect($beneficiary->member_id)->toBe($fixture['member']->id);
    expect($beneficiary->program_id)->toBe($program->id);
    expect($beneficiary->beneficiary_number)->not->toBeNull();

    $this->assertDatabaseHas('project_enrollments', [
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'beneficiary_id' => $beneficiary->id,
    ]);
});

test('service delivery operations feed the dashboard with attendance placements outcomes and geography', function () {
    $fixture = makePhaseFourFixture();
    $user = phaseFourUser([
        'domain.programs.view',
        'domain.programs.manage',
        'domain.projects.view',
        'domain.projects.manage',
        'domain.beneficiaries.view',
        'domain.beneficiaries.manage',
        'domain.activities.manage',
        'domain.attendance.manage',
        'domain.placements.manage',
        'domain.partnerships.manage',
        'domain.outcomes.manage',
        'domain.service-delivery.view',
        'domain.service-delivery.manage',
    ]);

    $program = Program::query()->create([
        'title' => 'Community Health Programme',
        'code' => 'CHP-001',
        'description' => 'Health outreach',
        'status' => 'active',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $fixture['projectManager']->id,
        'name' => 'Community Health Worker Training',
        'project_code' => 'CHW-001',
        'primary_location' => 'Clinic Training Centre',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Community health worker training project',
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $fixture['facilitator']->id,
        'province_id' => $fixture['province']->id,
        'training_venue_address' => 'Clinic Training Centre',
    ]);

    $beneficiary = app(\App\Domains\Beneficiaries\Services\BeneficiaryService::class)->store([
        'member_id' => $fixture['member']->id,
        'name' => 'Linked',
        'surname' => 'Member',
        'dob' => '2001-02-03',
        'age' => 25,
        'id_number' => $fixture['member']->id_number,
        'email' => $fixture['member']->email,
        'phone' => $fixture['member']->phone,
        'gender' => 'female',
        'program_id' => $program->id,
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'enrolment_date' => '2026-04-01',
        'participation_status' => 'active',
        'placement_status' => 'placed',
        'member_type' => 'Beneficiary',
        'street_address' => '99 Township Road',
        'city' => 'Johannesburg',
        'province_id' => $fixture['province']->id,
        'postal_code' => '1800',
        'highest_qualification' => 'Diploma',
        'attendance_status' => 'active',
    ]);

    $activity = ProjectActivity::query()->create([
        'project_id' => $project->id,
        'name' => 'Health Outreach Workshop',
        'planned_date' => '2026-04-10',
        'status' => 'in_progress',
        'assigned_team' => 'Programme Team',
    ]);

    $this->actingAs($user)->post('/service-delivery/attendance', [
        'member_id' => $fixture['member']->id,
        'beneficiary_id' => $beneficiary->id,
        'program_id' => $program->id,
        'project_id' => $project->id,
        'project_activity_id' => $activity->id,
        'attendance_type' => 'training',
        'attendance_date' => '2026-04-10',
        'attendance_status' => 'present',
    ])->assertRedirect();

    $this->actingAs($user)->post('/service-delivery/placements', [
        'beneficiary_id' => $beneficiary->id,
        'employer' => 'City Clinic',
        'opportunity_type' => 'employment',
        'placement_date' => '2026-05-01',
        'completion_date' => '2026-12-31',
        'status' => 'active',
        'notes' => 'Placed after training completion.',
    ])->assertRedirect();

    $this->actingAs($user)->post('/service-delivery/outcomes', [
        'program_id' => $program->id,
        'name' => 'Jobs Created',
        'target' => 50,
        'actual' => 12,
        'reporting_period' => 'Q2 2026',
    ])->assertRedirect();

    $this->actingAs($user)->post('/service-delivery/partnerships', [
        'organisation' => 'Health NGO',
        'contact_person' => 'Partner Lead',
        'contact_email' => 'partner@example.test',
        'contact_phone' => '0100000000',
        'partnership_type' => 'ngo',
        'status' => 'active',
        'program_ids' => [$program->id],
    ])->assertRedirect();

    expect(ServiceAttendance::query()->count())->toBe(1);
    expect(BeneficiaryPlacement::query()->where('opportunity_type', 'employment')->count())->toBe(1);
    expect(ProgrammeOutcome::query()->where('name', 'Jobs Created')->count())->toBe(1);

    $this->actingAs($user)
        ->get('/service-delivery')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ServiceDelivery/Dashboard')
            ->where('dashboard.programmes.total', 1)
            ->where('dashboard.projects.active', 1)
            ->where('dashboard.beneficiaries.active', 1)
            ->where('dashboard.placements.employment', 1)
            ->where('dashboard.attendance.present', 1)
            ->where('dashboard.outcomes.actual_total', 12)
            ->where('dashboard.partnerships.active', 1)
            ->has('dashboard.geography.provinces', 1)
            ->where('dashboard.geography.provinces.0.name', $fixture['province']->name)
            ->where('dashboard.geography.townships.0.name', $fixture['township']->name)
            ->where('dashboard.geography.branches.0.name', $fixture['branch']->name)
        );
});

test('service delivery write routes enforce granular permissions', function () {
    $fixture = makePhaseFourFixture();
    $viewer = phaseFourUser(['domain.service-delivery.view']);

    $program = Program::query()->create([
        'title' => 'Township Innovation Programme',
        'code' => 'TIP-001',
        'description' => 'Innovation programme',
        'status' => 'active',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $fixture['projectManager']->id,
        'name' => 'Innovation Lab',
        'project_code' => 'LAB-001',
        'primary_location' => 'Innovation Hub',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Innovation lab project',
    ]);

    $location = ProjectLocation::query()->create([
        'project_id' => $project->id,
        'facilitator_id' => $fixture['facilitator']->id,
        'province_id' => $fixture['province']->id,
        'training_venue_address' => 'Innovation Hub',
    ]);

    $beneficiary = app(\App\Domains\Beneficiaries\Services\BeneficiaryService::class)->store([
        'member_id' => $fixture['member']->id,
        'name' => 'Linked',
        'surname' => 'Member',
        'dob' => '2001-02-03',
        'age' => 25,
        'id_number' => $fixture['member']->id_number,
        'email' => $fixture['member']->email,
        'phone' => $fixture['member']->phone,
        'gender' => 'female',
        'program_id' => $program->id,
        'project_id' => $project->id,
        'project_location_id' => $location->id,
        'enrolment_date' => '2026-04-01',
        'participation_status' => 'registered',
        'member_type' => 'Beneficiary',
        'attendance_status' => 'active',
    ]);

    $this->actingAs($viewer)
        ->post('/service-delivery/placements', [
            'beneficiary_id' => $beneficiary->id,
            'employer' => 'Blocked Employer',
            'opportunity_type' => 'internship',
            'status' => 'active',
        ])
        ->assertForbidden();
});
