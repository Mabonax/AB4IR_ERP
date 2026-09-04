<?php

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentCriterion;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentDimension;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentGap;
use App\Domains\BusinessDevelopment\Models\EnterpriseDiagnostic;
use App\Domains\BusinessDevelopment\Services\EnterpriseDevelopmentService;
use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\EnterpriseDevelopmentFrameworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(AccessControlSeeder::class);
    $this->seed(EnterpriseDevelopmentFrameworkSeeder::class);
});

function createEnterpriseDevelopmentManager(): User
{
    $user = User::factory()->create();

    foreach ([
        'domain.business-development.view',
        'domain.business-development.manage',
        'enterprise-development.profile.view',
        'enterprise-development.framework.view',
        'enterprise-development.framework.manage',
        'enterprise-development.diagnostics.create',
        'enterprise-development.diagnostics.edit',
        'enterprise-development.diagnostics.complete',
        'enterprise-development.needs.manage',
        'enterprise-development.plans.manage',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'department-manager-business-development', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::query()->pluck('name')->all());
    $user->assignRole($role);

    return $user;
}

function createEnterpriseDevelopmentIncubatee(): BdsIncubatee
{
    $provinceId = DB::table('provinces')->insertGetId([
        'name' => 'Gauteng',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return BdsIncubatee::query()->create([
        'full_name' => 'Jane Founder',
        'id_number' => fake()->unique()->numerify('#############'),
        'gender' => 'female',
        'mobile_number' => '0711111111',
        'email' => fake()->unique()->safeEmail(),
        'company_name' => 'Acme Technologies',
        'company_registration_number' => strtoupper(fake()->unique()->bothify('K######')),
        'position_in_company' => 'Founder',
        'majority_shareholding' => 'Yes',
        'current_number_of_employees' => 4,
        'physical_address' => '123 Street',
        'website_address' => 'https://example.test',
        'years_in_operation' => 1,
        'province_id' => $provinceId,
        'has_business_plan' => true,
        'relevant_skill_set' => 'Technical leadership',
        'technology_product_service' => 'Digital platform',
        'technology_stage_of_development' => 'Prototype',
        'status' => 'active',
        'incubated_date' => now()->toDateString(),
    ]);
}

test('framework seeder creates configurable dimensions and criteria', function () {
    expect(EnterpriseDevelopmentDimension::query()->count())->toBe(11);
    expect(EnterpriseDevelopmentCriterion::query()->where('evidence_required', true)->count())->toBeGreaterThan(0);
    expect(EnterpriseDevelopmentDimension::query()->where('code', 'compliance_governance')->exists())->toBeTrue();
});

test('baseline diagnostic snapshots criteria and prevents duplicate baseline', function () {
    $manager = createEnterpriseDevelopmentManager();
    $incubatee = createEnterpriseDevelopmentIncubatee();

    $this->actingAs($manager)
        ->post(route('business-development.incubatees.enterprise-development.diagnostics.store', $incubatee), [
            'assessment_type' => 'baseline',
            'assessment_date' => now()->toDateString(),
            'baseline_employees' => 4,
            'baseline_turnover' => 12000,
        ])
        ->assertRedirect();

    $diagnostic = EnterpriseDiagnostic::query()->firstOrFail();
    expect($diagnostic->criteria()->count())->toBe(EnterpriseDevelopmentCriterion::query()->where('active', true)->count());
    expect($diagnostic->outcome_baseline['employees'])->toBe(4);

    $criterion = $diagnostic->criteria()->firstOrFail();
    $originalName = $criterion->criterion_name;
    EnterpriseDevelopmentCriterion::query()->where('code', $criterion->criterion_code)->update(['name' => 'Changed Name']);
    expect($criterion->fresh()->criterion_name)->toBe($originalName);

    $this->actingAs($manager)
        ->post(route('business-development.incubatees.enterprise-development.diagnostics.store', $incubatee), [
            'assessment_type' => 'baseline',
            'assessment_date' => now()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors(['assessment_type']);
});

test('diagnostic calculations ignore not applicable and generate gaps', function () {
    $manager = createEnterpriseDevelopmentManager();
    $incubatee = createEnterpriseDevelopmentIncubatee();

    $diagnostic = app(EnterpriseDevelopmentService::class)->createDiagnostic($incubatee, [
        'assessment_type' => 'baseline',
        'assessment_date' => now()->toDateString(),
    ], $manager);

    $criteria = $diagnostic->criteria()->get()->values();
    $payload = $criteria->map(fn ($criterion, int $index) => [
        'id' => $criterion->id,
        'maturity_status' => $index === 0 ? 'not_applicable' : ($index === 1 ? 'not_started' : 'verified'),
        'assessor_observation' => $index === 1 ? 'No process exists yet.' : null,
    ])->all();

    app(EnterpriseDevelopmentService::class)->saveCriteria($diagnostic, $payload, $manager);
    $completed = app(EnterpriseDevelopmentService::class)->completeDiagnostic($diagnostic->refresh(), $manager);

    expect($completed->status)->toBe('completed');
    expect((float) $completed->overall_score)->toBeGreaterThan(0);
    expect(EnterpriseDevelopmentGap::query()->where('enterprise_diagnostic_id', $completed->id)->count())->toBe(1);

    expect(fn () => app(EnterpriseDevelopmentService::class)->saveCriteria($completed, $payload, $manager))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('compliance evidence can be linked without duplicating files', function () {
    $manager = createEnterpriseDevelopmentManager();
    $incubatee = createEnterpriseDevelopmentIncubatee();
    $folder = DocumentFolder::query()->create([
        'name' => 'Enterprise Evidence',
        'folder_type' => DocumentFolder::TYPE_STANDARD,
        'created_by' => $manager->id,
    ]);
    $document = DocumentFile::query()->create([
        'folder_id' => $folder->id,
        'title' => 'CIPC Certificate',
        'disk' => 'public',
        'file_path' => 'documents/cipc.pdf',
        'original_name' => 'cipc.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1234,
        'version' => 1,
        'status' => 'available',
        'uploaded_by' => $manager->id,
    ]);

    $diagnostic = app(EnterpriseDevelopmentService::class)->createDiagnostic($incubatee, [
        'assessment_type' => 'baseline',
        'assessment_date' => now()->toDateString(),
    ], $manager);
    $compliance = $diagnostic->criteria()->where('dimension_code', 'compliance_governance')->firstOrFail();

    app(EnterpriseDevelopmentService::class)->saveCriteria($diagnostic, [[
        'id' => $compliance->id,
        'maturity_status' => 'verified',
        'assessor_observation' => 'Certificate checked.',
        'evidence_document_file_id' => $document->id,
        'evidence_label' => 'CIPC certificate',
        'verified_at' => now()->toDateString(),
    ]], $manager);

    expect($compliance->fresh()->evidence_document_file_id)->toBe($document->id);
});

test('development need and plan can be created from diagnostic gap', function () {
    $manager = createEnterpriseDevelopmentManager();
    $incubatee = createEnterpriseDevelopmentIncubatee();
    $diagnostic = app(EnterpriseDevelopmentService::class)->createDiagnostic($incubatee, [
        'assessment_type' => 'baseline',
        'assessment_date' => now()->toDateString(),
    ], $manager);

    $payload = $diagnostic->criteria()->get()->map(fn ($criterion) => [
        'id' => $criterion->id,
        'maturity_status' => $criterion->required ? 'not_started' : 'not_applicable',
        'assessor_observation' => 'Needs support.',
    ])->all();

    app(EnterpriseDevelopmentService::class)->saveCriteria($diagnostic, $payload, $manager);
    app(EnterpriseDevelopmentService::class)->completeDiagnostic($diagnostic->refresh(), $manager);
    $gap = EnterpriseDevelopmentGap::query()->firstOrFail();

    $this->actingAs($manager)
        ->post(route('business-development.enterprise-development.gaps.needs.store', $gap), [
            'title' => 'Enterprise foundation support',
            'priority' => 'high',
            'reason' => 'Required foundation criteria are not started.',
        ])
        ->assertRedirect();

    $need = $incubatee->developmentNeeds()->firstOrFail();

    $this->actingAs($manager)
        ->post(route('business-development.incubatees.enterprise-development.plans.store', $incubatee), [
            'title' => 'Acme Development Plan',
            'baseline_diagnostic_id' => $diagnostic->id,
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'items' => [[
                'development_need_id' => $need->id,
                'objective' => 'Establish core enterprise foundation documents.',
                'priority' => 'high',
                'status' => 'open',
                'responsible_user_id' => $manager->id,
            ]],
        ])
        ->assertRedirect();

    expect($incubatee->developmentPlans()->with('items')->firstOrFail()->items)->toHaveCount(1);

    $planItem = $incubatee->developmentPlans()->with('items')->firstOrFail()->items->first();

    $this->actingAs($manager)
        ->put(route('business-development.enterprise-development.needs.update', $need), [
            'status' => 'planned',
            'priority' => 'medium',
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->put(route('business-development.enterprise-development.plan-items.update', $planItem), [
            'status' => 'in_progress',
            'notes' => 'Assigned to business development manager.',
            'responsible_user_id' => null,
        ])
        ->assertRedirect();

    expect($need->fresh()->status)->toBe('planned');
    expect($planItem->fresh()->status)->toBe('in_progress');
    expect($planItem->fresh()->responsible_user_id)->toBeNull();
});

test('incubatee exposes enterprise development workspace route', function () {
    $manager = createEnterpriseDevelopmentManager();
    $incubatee = createEnterpriseDevelopmentIncubatee();

    $this->actingAs($manager)
        ->get(route('business-development.incubatees.enterprise-development.show', $incubatee))
        ->assertOk();
});
