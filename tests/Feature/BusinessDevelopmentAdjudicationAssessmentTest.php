<?php

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Services\BdsApplicationService;
use App\Models\User;
use Database\Seeders\AdjudicationSectionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AdjudicationSectionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createUserWithBusinessDevelopmentPermission(bool $asAdmin = false): User
{
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'domain.business-development.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'domain.business-development.manage', 'guard_name' => 'web']);

    $user->givePermissionTo(['domain.business-development.view', 'domain.business-development.manage']);

    if ($asAdmin) {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);
    }

    return $user;
}

function createScoreOnlyJudge(): User
{
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'business-development.adjudications.score', 'guard_name' => 'web']);

    $user->givePermissionTo(['business-development.adjudications.score']);

    return $user;
}

function createSmmeApplication(): int
{
    $provinceId = DB::table('provinces')->insertGetId([
        'name' => 'Gauteng',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return (int) DB::table('bds_applications')->insertGetId([
        'full_name' => 'Jane Founder',
        'id_number' => (string) fake()->unique()->numerify('#############'),
        'gender' => 'female',
        'mobile_number' => '0711111111',
        'email' => fake()->unique()->safeEmail(),
        'company_name' => 'Acme SMME',
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
        'assessment_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function scorePayload(): array
{
    $sections = DB::table('bd_adjudication_sections')->orderBy('sort_order')->get();

    return $sections->map(fn ($section) => [
        'section_id' => $section->id,
        'score' => min(5, (int) $section->max_points),
        'comment' => "Comment for {$section->title}",
    ])->all();
}

test('judge can use adjudication resource routes', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.index'))
        ->assertOk();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.create'))
        ->assertOk();

    $storeResponse = $this->actingAs($judge)->post(route('business-development.adjudications.store'), [
        'smme_id' => $smmeId,
        'platform_name' => 'Acme Platform',
        'adjudication_date' => now()->toDateString(),
        'development_stage' => 'prototype',
        'additional_notes' => 'Initial draft',
        'scores' => scorePayload(),
    ]);

    $assessment = AdjudicationAssessment::query()->firstOrFail();

    $storeResponse->assertRedirect(route('business-development.adjudications.show', $assessment));

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.show', $assessment))
        ->assertOk();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.edit', $assessment))
        ->assertOk();

    $this->actingAs($judge)
        ->put(route('business-development.adjudications.update', $assessment), [
            'smme_id' => $smmeId,
            'platform_name' => 'Acme Platform Updated',
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'complete_product',
            'additional_notes' => 'Updated notes',
            'scores' => scorePayload(),
        ])
        ->assertRedirect(route('business-development.adjudications.edit', $assessment));

    $this->actingAs($judge)
        ->delete(route('business-development.adjudications.destroy', $assessment))
        ->assertRedirect(route('business-development.adjudications.index'));
});

test('score-only panelist can use adjudication routes for owned assessments', function () {
    $judge = createScoreOnlyJudge();
    $smmeId = createSmmeApplication();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.index'))
        ->assertOk();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.create'))
        ->assertOk();

    $this->actingAs($judge)
        ->post(route('business-development.adjudications.store'), [
            'smme_id' => $smmeId,
            'platform_name' => 'Panel Platform',
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'prototype',
            'additional_notes' => 'Panel draft',
            'scores' => scorePayload(),
        ])
        ->assertRedirect();

    $assessment = AdjudicationAssessment::query()->firstOrFail();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.show', $assessment))
        ->assertOk();

    $this->actingAs($judge)
        ->get(route('business-development.adjudications.edit', $assessment))
        ->assertOk();

    $this->actingAs($judge)
        ->put(route('business-development.adjudications.update', $assessment), [
            'smme_id' => $smmeId,
            'platform_name' => 'Panel Platform Updated',
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'complete_product',
            'additional_notes' => 'Panel update',
            'scores' => scorePayload(),
        ])
        ->assertRedirect(route('business-development.adjudications.edit', $assessment));

    $this->actingAs($judge)
        ->post(route('business-development.adjudications.submit', $assessment), [
            'result' => 'rejected',
        ])
        ->assertRedirect(route('business-development.adjudications.show', $assessment));
});

test('assessment cannot be updated after submit', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    $assessment = AdjudicationAssessment::query()->create([
        'smme_id' => $smmeId,
        'judge_id' => $judge->id,
        'platform_name' => 'Locked Platform',
        'adjudication_date' => now()->toDateString(),
        'development_stage' => 'mvp',
        'status' => 'draft',
        'total_score' => 0,
    ]);

    $this->actingAs($judge)
        ->put(route('business-development.adjudications.update', $assessment), [
            'smme_id' => $smmeId,
            'platform_name' => 'Before submit update',
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'prototype',
            'additional_notes' => null,
            'scores' => scorePayload(),
        ])
        ->assertRedirect();

    $this->actingAs($judge)
        ->post(route('business-development.adjudications.submit', $assessment), [
            'result' => 'rejected',
        ])
        ->assertRedirect(route('business-development.adjudications.show', $assessment));

    $this->actingAs($judge)
        ->put(route('business-development.adjudications.update', $assessment), [
            'smme_id' => $smmeId,
            'platform_name' => 'Should fail',
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'prototype',
            'additional_notes' => 'blocked',
            'scores' => scorePayload(),
        ])
        ->assertForbidden();
});

test('submitting adjudication as incubated creates incubatee and updates application result', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    $assessment = AdjudicationAssessment::query()->create([
        'smme_id' => $smmeId,
        'judge_id' => $judge->id,
        'platform_name' => 'Outcome Platform',
        'adjudication_date' => now()->toDateString(),
        'development_stage' => 'prototype',
        'status' => 'draft',
        'total_score' => 22,
    ]);

    $this->actingAs($judge)
        ->post(route('business-development.adjudications.submit', $assessment), [
            'result' => 'incubated',
        ])
        ->assertRedirect(route('business-development.adjudications.show', $assessment));

    $this->assertDatabaseHas('bds_applications', [
        'id' => $smmeId,
        'adjudication_result' => 'incubated',
    ]);

    $application = DB::table('bds_applications')->where('id', $smmeId)->first();

    $this->assertDatabaseHas('bds_incubatees', [
        'bds_application_id' => $smmeId,
        'id_number' => $application->id_number,
        'company_registration_number' => $application->company_registration_number,
        'status' => 'active',
    ]);
});

test('score validation respects section max points', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    $scores = scorePayload();
    $scores[0]['score'] = 999;

    $this->actingAs($judge)
        ->post(route('business-development.adjudications.store'), [
            'smme_id' => $smmeId,
            'platform_name' => 'Validation Platform',
            'adjudication_date' => now()->toDateString(),
            'development_stage' => 'prototype',
            'additional_notes' => null,
            'scores' => $scores,
        ])
        ->assertSessionHasErrors(['scores.0.score']);
});

test('total score is recalculated server side', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    $scores = scorePayload();
    $scores[0]['score'] = 10;
    $scores[1]['score'] = 7;
    $scores[2]['score'] = 13;
    $scores[3]['score'] = 9;

    $this->actingAs($judge)->post(route('business-development.adjudications.store'), [
        'smme_id' => $smmeId,
        'platform_name' => 'Total Platform',
        'adjudication_date' => now()->toDateString(),
        'development_stage' => 'prototype',
        'additional_notes' => 'Total test',
        'scores' => $scores,
    ]);

    $assessment = AdjudicationAssessment::query()->firstOrFail();

    expect($assessment->total_score)->toBe(39);
});

test('admin can view all assessments and unlock submitted ones', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $admin = createUserWithBusinessDevelopmentPermission(asAdmin: true);
    $smmeId = createSmmeApplication();

    $assessment = AdjudicationAssessment::query()->create([
        'smme_id' => $smmeId,
        'judge_id' => $judge->id,
        'platform_name' => 'Submitted Platform',
        'adjudication_date' => now()->toDateString(),
        'development_stage' => 'prototype',
        'status' => 'submitted',
        'total_score' => 20,
        'submitted_at' => now(),
    ]);

    foreach (scorePayload() as $row) {
        DB::table('bd_adjudication_scores')->insert([
            'assessment_id' => $assessment->id,
            'section_id' => $row['section_id'],
            'score' => $row['score'],
            'comment' => $row['comment'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('business-development.adjudications.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('business-development.adjudications.unlock', $assessment))
        ->assertRedirect(route('business-development.adjudications.edit', $assessment));

    $this->assertDatabaseHas('bd_adjudication_assessments', [
        'id' => $assessment->id,
        'status' => 'draft',
    ]);
});

test('applications with submitted adjudications cannot be reassessed', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    AdjudicationAssessment::query()->create([
        'smme_id' => $smmeId,
        'judge_id' => $judge->id,
        'platform_name' => 'Locked Assessment',
        'adjudication_date' => now()->toDateString(),
        'development_stage' => 'prototype',
        'status' => 'submitted',
        'total_score' => 18,
        'submitted_at' => now(),
    ]);

    $this->actingAs($judge)
        ->post(route('business-development.applications.assess', $smmeId), [
            'assessment_status' => 'accepted',
        ])
        ->assertForbidden();
});

test('pitch scheduling must use a future date and time', function () {
    $judge = createUserWithBusinessDevelopmentPermission(asAdmin: true);
    $smmeId = createSmmeApplication();

    DB::table('bds_applications')->where('id', $smmeId)->update([
        'assessment_status' => 'accepted',
        'updated_at' => now(),
    ]);

    $this->actingAs($judge);

    expect(fn () => app(BdsApplicationService::class)->schedulePitch($smmeId, [
            'pitch_scheduled_at' => now()->subDay()->toDateTimeString(),
            'pitch_notes' => 'Too late',
        ]))->toThrow(ValidationException::class);
});

test('application resource exposes workflow blockers before pitching and adjudication', function () {
    $judge = createUserWithBusinessDevelopmentPermission();
    $smmeId = createSmmeApplication();

    $response = $this->actingAs($judge)
        ->get(route('business-development.applications.show', $smmeId))
        ->assertOk();

    $application = $response->viewData('page')['props']['application']['data'] ?? $response->viewData('application')['data'] ?? null;

    expect($application)->not->toBeNull();
    expect($application['workflow_summary']['pitch']['ready'])->toBeFalse();
    expect($application['workflow_summary']['adjudication']['ready'])->toBeFalse();
    expect($application['workflow_summary']['pitch']['blockers'])->toContain('Only accepted applications can be scheduled for pitching.');
    expect($application['workflow_summary']['adjudication']['blockers'])->toContain('A pitch must be scheduled before adjudication can start.');
});
