<?php

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Models\BdsPitchSessionProspect;
use App\Domains\BusinessDevelopment\Services\BdsPitchSessionService;
use App\Models\User;
use Database\Seeders\AdjudicationSectionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AdjudicationSectionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createBusinessDevelopmentManager(): User
{
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'domain.business-development.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'domain.business-development.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'department-manager-business-development', 'guard_name' => 'web']);
    $role->givePermissionTo(['domain.business-development.view', 'domain.business-development.manage']);

    $user->assignRole($role);

    return $user;
}

function createBusinessDevelopmentPanelist(): User
{
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'domain.business-development.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'domain.business-development.manage', 'guard_name' => 'web']);
    $user->givePermissionTo(['domain.business-development.view', 'domain.business-development.manage']);

    return $user;
}

function createAcceptedProspect(string $company): int
{
    $provinceId = DB::table('provinces')->insertGetId([
        'name' => $company.' Province',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return (int) DB::table('bds_applications')->insertGetId([
        'full_name' => $company.' Founder',
        'id_number' => (string) fake()->unique()->numerify('#############'),
        'gender' => 'female',
        'mobile_number' => '0711111111',
        'email' => fake()->unique()->safeEmail(),
        'company_name' => $company,
        'company_registration_number' => strtoupper(fake()->unique()->bothify('K######')),
        'position_in_company' => 'Founder',
        'majority_shareholding' => 'Yes',
        'current_number_of_employees' => 5,
        'physical_address' => '123 Street',
        'website_address' => 'https://example.com',
        'years_in_operation' => 2,
        'province_id' => $provinceId,
        'has_business_plan' => true,
        'relevant_skill_set' => 'Operations',
        'technology_product_service' => 'Platform',
        'technology_stage_of_development' => 'Prototype',
        'application_date' => now()->toDateString(),
        'assessment_status' => 'accepted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function sessionScorePayload(): array
{
    return DB::table('bd_adjudication_sections')
        ->orderBy('sort_order')
        ->get()
        ->map(fn ($section) => [
            'section_id' => $section->id,
            'score' => min(5, (int) $section->max_points),
            'comment' => "Comment for {$section->title}",
        ])->all();
}

test('pitch session scheduling attaches panelists and prospects and updates pitch schedule', function () {
    $manager = createBusinessDevelopmentManager();
    $panelist = createBusinessDevelopmentPanelist();
    $prospectId = createAcceptedProspect('Acme Prospect');

    $session = app(BdsPitchSessionService::class)->createSession([
        'title' => 'May Pitch Panel',
        'scheduled_for' => now()->addDays(3)->toDateTimeString(),
        'venue' => 'Boardroom A',
        'notes' => 'Primary BDS pitch day',
        'panelists' => [$manager->id, $panelist->id],
        'prospects' => [$prospectId],
    ], $manager);

    expect($session->panelists)->toHaveCount(2);
    expect($session->prospects)->toHaveCount(1);

    $this->assertDatabaseHas('bds_applications', [
        'id' => $prospectId,
    ]);

    expect(DB::table('bds_applications')->where('id', $prospectId)->value('pitch_scheduled_at'))->not->toBeNull();
});

test('pitch session consolidation aggregates submitted panel scorecards and manager approval incubates the prospect', function () {
    $manager = createBusinessDevelopmentManager();
    $technicalPanelist = createBusinessDevelopmentPanelist();
    $prospectId = createAcceptedProspect('Consolidated Prospect');

    $session = app(BdsPitchSessionService::class)->createSession([
        'title' => 'Panel Day',
        'scheduled_for' => now()->addDays(2)->toDateTimeString(),
        'venue' => 'Innovation Hub',
        'notes' => 'Consolidated scoring day',
        'panelists' => [$manager->id, $technicalPanelist->id],
        'prospects' => [$prospectId],
    ], $manager);

    $session = app(BdsPitchSessionService::class)->startSession($session, $manager);

    $scores = sessionScorePayload();

    foreach ([[$manager, 'BDS Platform'], [$technicalPanelist, 'Technical Platform']] as [$actor, $platform]) {
        $assessment = AdjudicationAssessment::query()->create([
            'smme_id' => $prospectId,
            'pitch_session_id' => $session->id,
            'judge_id' => $actor->id,
            'platform_name' => $platform,
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'prototype',
            'status' => 'submitted',
            'total_score' => 20,
            'submitted_at' => now(),
        ]);

        foreach ($scores as $row) {
            DB::table('bd_adjudication_scores')->insert([
                'assessment_id' => $assessment->id,
                'section_id' => $row['section_id'],
                'score' => $row['score'],
                'comment' => $row['comment'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    $prospect = BdsPitchSessionProspect::query()->where('pitch_session_id', $session->id)->where('bds_application_id', $prospectId)->firstOrFail();

    $consolidated = app(BdsPitchSessionService::class)->consolidateProspect($prospect, $manager);
    expect($consolidated->submitted_assessments_count)->toBe(2);
    expect($consolidated->consolidated_total_score)->toBe(40);

    $approved = app(BdsPitchSessionService::class)->approveProspect($consolidated, $manager, 'incubated', 'Panel recommends incubation.');

    expect($approved->manager_decision)->toBe('incubated');
    $this->assertDatabaseHas('bds_applications', [
        'id' => $prospectId,
        'adjudication_result' => 'incubated',
    ]);
    $this->assertDatabaseHas('bds_incubatees', [
        'bds_application_id' => $prospectId,
        'status' => 'active',
    ]);
});
