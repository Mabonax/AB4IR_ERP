<?php

use App\Domains\Employment\Models\EmploymentProfile;
use App\Domains\Geography\Models\Branch;
use App\Domains\Geography\Models\Municipality;
use App\Domains\Geography\Models\Region;
use App\Domains\Geography\Models\Township;
use App\Domains\Geography\Models\Ward;
use App\Domains\Members\Models\Member;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeHumanCapitalManager(): User
{
    $user = User::factory()->create();
    grantDomainAccess($user, 'members');
    grantDomainAccess($user, 'geography');
    grantDomainAccess($user, 'human-capital');
    grantDomainAccess($user, 'reporting');

    return $user->refresh();
}

function buildHumanCapitalGeography(): array
{
    $province = Provinces::query()->create(['name' => 'Gauteng']);
    $municipality = Municipality::query()->create([
        'province_id' => $province->id,
        'name' => 'City of Tshwane',
        'code' => 'TSH',
    ]);
    $region = Region::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'name' => 'Region 1',
        'code' => 'R1',
    ]);
    $township = Township::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'name' => 'Soshanguve Block L',
    ]);
    $ward = Ward::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'township_id' => $township->id,
        'name' => 'Ward 12',
        'code' => '12',
    ]);
    $branch = Branch::query()->create([
        'province_id' => $province->id,
        'municipality_id' => $municipality->id,
        'region_id' => $region->id,
        'township_id' => $township->id,
        'ward_id' => $ward->id,
        'name' => 'Block L Branch',
        'code' => 'BLL',
    ]);

    return compact('province', 'municipality', 'region', 'township', 'ward', 'branch');
}

test('human capital managers can register members with qualifications skills employment and geography', function () {
    $user = makeHumanCapitalManager();
    $geo = buildHumanCapitalGeography();

    $this->actingAs($user)->post(route('members.store'), [
        'first_name' => 'Lerato',
        'last_name' => 'Mokoena',
        'id_number' => '9201015800087',
        'date_of_birth' => '1992-01-01',
        'gender' => 'Female',
        'phone' => '0820000000',
        'email' => 'lerato@example.test',
        'physical_address' => '123 Block L',
        'province_id' => $geo['province']->id,
        'municipality_id' => $geo['municipality']->id,
        'region_id' => $geo['region']->id,
        'township_id' => $geo['township']->id,
        'ward_id' => $geo['ward']->id,
        'branch_id' => $geo['branch']->id,
        'member_type' => 'Graduate',
        'status' => 'active',
        'disability_status' => false,
        'youth_indicator' => true,
        'veteran_indicator' => false,
        'household_size' => 4,
        'dependants' => 1,
        'employment' => [
            'employment_status' => 'Unemployed',
            'occupation' => 'Junior Developer',
            'industry' => 'Technology',
            'years_experience' => 2,
            'monthly_income_band' => 'No income',
        ],
        'qualifications' => [[
            'qualification_type' => 'Diploma',
            'institution' => 'Tshwane South TVET College',
            'qualification_name' => 'National Diploma in Information Technology',
            'field_of_study' => 'Information Technology',
            'nqf_level' => 'NQF 6',
            'completed_flag' => true,
            'completion_year' => 2024,
        ]],
        'skills' => [[
            'skill_name' => 'Software Development',
            'category' => 'Digital',
            'proficiency_level' => 'Intermediate',
            'years_experience' => 2,
        ]],
        'work_experiences' => [[
            'employer' => 'Tech Hub',
            'position' => 'Intern Developer',
            'industry' => 'Technology',
            'current_employer_flag' => false,
            'responsibilities' => 'Built internal tools.',
        ]],
        'interests' => [[
            'interest_type' => 'Employment Interest',
            'opportunity_category' => 'Software Development',
            'notes' => 'Ready for placement',
        ]],
        'assignments' => [[
            'assignment_type' => 'branch',
            'assignable_id' => $geo['branch']->id,
            'member_role' => 'Volunteer Lead',
        ]],
    ])->assertRedirect()->assertSessionHas('success', 'Member registered.');

    $member = Member::query()->firstOrFail();

    expect($member->township?->name)->toBe('Soshanguve Block L')
        ->and($member->qualifications()->count())->toBe(1)
        ->and($member->skills()->count())->toBe(1)
        ->and($member->workExperiences()->count())->toBe(1)
        ->and($member->opportunityInterests()->count())->toBe(1)
        ->and($member->assignments()->count())->toBe(1);

    expect(EmploymentProfile::query()->where('member_id', $member->id)->value('employment_status'))
        ->toBe('Unemployed');
});

test('blank nested member rows are ignored so registration reaches completion', function () {
    $user = makeHumanCapitalManager();

    $this->actingAs($user)->post(route('members.store'), [
        'first_name' => 'Neo',
        'last_name' => 'Mabaso',
        'id_number' => '9001015800087',
        'member_type' => 'Volunteer',
        'status' => 'active',
        'disability_status' => false,
        'youth_indicator' => true,
        'veteran_indicator' => false,
        'qualifications' => [[
            'qualification_type' => '',
            'institution' => '',
            'qualification_name' => '',
            'field_of_study' => '',
            'nqf_level' => '',
            'start_date' => '',
            'end_date' => '',
            'completed_flag' => false,
            'completion_year' => '',
        ]],
        'skills' => [[
            'skill_name' => '',
            'category' => '',
            'proficiency_level' => '',
            'years_experience' => '',
        ]],
        'work_experiences' => [[
            'employer' => '',
            'position' => '',
            'industry' => '',
            'start_date' => '',
            'end_date' => '',
            'current_employer_flag' => false,
            'responsibilities' => '',
        ]],
        'interests' => [[
            'interest_type' => '',
            'opportunity_category' => '',
            'notes' => '',
        ]],
        'assignments' => [[
            'assignment_type' => '',
            'assignable_id' => '',
            'member_role' => '',
            'started_at' => '',
            'ended_at' => '',
            'notes' => '',
        ]],
    ])->assertRedirect()->assertSessionHas('success', 'Member registered.');

    $member = Member::query()->where('id_number', '9001015800087')->firstOrFail();

    expect($member->qualifications()->count())->toBe(0)
        ->and($member->skills()->count())->toBe(0)
        ->and($member->workExperiences()->count())->toBe(0)
        ->and($member->opportunityInterests()->count())->toBe(0)
        ->and($member->assignments()->count())->toBe(0);
});

test('invalid nested member rows fail clearly without creating partial records', function () {
    $user = makeHumanCapitalManager();

    $this->actingAs($user)->from(route('members.create'))->post(route('members.store'), [
        'first_name' => 'Aphiwe',
        'last_name' => 'Dlamini',
        'id_number' => '9101015800087',
        'member_type' => 'Graduate',
        'status' => 'active',
        'disability_status' => false,
        'youth_indicator' => true,
        'veteran_indicator' => false,
        'qualifications' => [[
            'qualification_type' => 'Diploma',
            'institution' => '',
            'qualification_name' => '',
            'field_of_study' => '',
            'completed_flag' => true,
        ]],
    ])->assertRedirect(route('members.create'))
        ->assertSessionHasErrors([
            'qualifications.0.institution',
            'qualifications.0.qualification_name',
            'qualifications.0.field_of_study',
        ]);

    $this->assertDatabaseMissing('members', [
        'id_number' => '9101015800087',
    ]);
});

test('geography registry can be extended from the ui workflow', function () {
    $user = makeHumanCapitalManager();
    $province = Provinces::query()->create(['name' => 'Limpopo']);

    $this->actingAs($user)->post(route('geography.store'), [
        'type' => 'municipality',
        'province_id' => $province->id,
        'name' => 'Polokwane Local Municipality',
        'code' => 'PLK',
    ])->assertRedirect()->assertSessionHas('success', 'Geographic record added.');

    $this->assertDatabaseHas('municipalities', [
        'name' => 'Polokwane Local Municipality',
        'province_id' => $province->id,
    ]);
});

test('human capital dashboard and reports expose analytics', function () {
    $user = makeHumanCapitalManager();
    $geo = buildHumanCapitalGeography();

    $member = Member::query()->create([
        'first_name' => 'Sipho',
        'last_name' => 'Nkosi',
        'id_number' => '9303035800087',
        'province_id' => $geo['province']->id,
        'municipality_id' => $geo['municipality']->id,
        'region_id' => $geo['region']->id,
        'township_id' => $geo['township']->id,
        'ward_id' => $geo['ward']->id,
        'branch_id' => $geo['branch']->id,
        'member_type' => 'Volunteer',
        'status' => 'active',
        'youth_indicator' => true,
    ]);

    $member->qualifications()->create([
        'qualification_type' => 'NATED',
        'institution' => 'TVET College',
        'qualification_name' => 'Mechanical Engineering N6',
        'field_of_study' => 'Mechanical Engineering',
        'completed_flag' => true,
        'completion_year' => 2025,
    ]);
    $member->skills()->create([
        'skill_name' => 'Welding',
        'category' => 'Technical',
        'proficiency_level' => 'Advanced',
        'years_experience' => 4,
    ]);
    $member->employmentProfile()->create([
        'employment_status' => 'Unemployed',
        'industry' => 'Engineering',
    ]);

    $this->actingAs($user)
        ->get(route('human-capital.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanCapital/Dashboard')
            ->where('stats.total_members', 1)
            ->where('stats.total_volunteers', 1)
        );

    $this->actingAs($user)
        ->get(route('human-capital.reports'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanCapital/Reports')
            ->where('townships.0.township_name', 'Soshanguve Block L')
        );

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.secondary.0.key', 'human-capital')
        );
});

test('human capital routes enforce permissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('members.index'))->assertForbidden();
    $this->actingAs($user)->get(route('human-capital.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('geography.index'))->assertForbidden();
});
