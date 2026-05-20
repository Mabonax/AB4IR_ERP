<?php

use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Domains\BusinessDevelopment\Services\BdsPitchSessionService;
use App\Models\User;
use Database\Seeders\AdjudicationSectionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AdjudicationSectionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createPitchSessionManager(): User
{
    $user = User::factory()->create();

    Permission::firstOrCreate(['name' => 'domain.business-development.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'domain.business-development.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'business-development.adjudications.score', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'department-manager-business-development', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'domain.business-development.view',
        'domain.business-development.manage',
        'business-development.adjudications.score',
    ]);

    $user->assignRole($role);

    return $user;
}

function createPitchPanelist(): User
{
    $user = User::factory()->create();

    grantDomainAccess($user, 'business-development');
    grantPermissions($user, ['business-development.adjudications.score']);

    return $user;
}

function createPitchProspect(string $company): int
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

test('manager can view and start a pitch session through web routes', function () {
    $manager = createPitchSessionManager();
    $panelist = createPitchPanelist();
    $prospectId = createPitchProspect('Ui Session Prospect');

    $session = app(BdsPitchSessionService::class)->createSession([
        'title' => 'UI Session',
        'scheduled_for' => now()->addDays(2)->toDateTimeString(),
        'venue' => 'Main Boardroom',
        'panelists' => [$manager->id, $panelist->id],
        'prospects' => [$prospectId],
    ], $manager);

    $this->actingAs($manager)
        ->get(route('business-development.pitch-sessions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('BusinessDevelopment/PitchSessions/Index')
            ->where('sessions.data.0.title', 'UI Session')
        );

    $this->actingAs($manager)
        ->get(route('business-development.pitch-sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('BusinessDevelopment/PitchSessions/Show')
            ->where('session.data.title', 'UI Session')
            ->where('session.data.prospects.0.company_name', 'Ui Session Prospect')
        );

    $this->actingAs($manager)
        ->post(route('business-development.pitch-sessions.start', $session))
        ->assertRedirect(route('business-development.pitch-sessions.show', $session));

    expect(BdsPitchSession::query()->findOrFail($session->id)->status)->toBe('in_progress');
});

test('assigned panelist can open their pitch session detail page', function () {
    $manager = createPitchSessionManager();
    $panelist = createPitchPanelist();
    $prospectId = createPitchProspect('Assigned Panel Prospect');

    $session = app(BdsPitchSessionService::class)->createSession([
        'title' => 'Assigned Session',
        'scheduled_for' => now()->addDays(3)->toDateTimeString(),
        'venue' => 'Innovation Hub',
        'panelists' => [$manager->id, $panelist->id],
        'prospects' => [$prospectId],
    ], $manager);

    $this->actingAs($panelist)
        ->get(route('business-development.pitch-sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('BusinessDevelopment/PitchSessions/Show')
            ->where('session.data.title', 'Assigned Session')
            ->where('session.data.prospects.0.company_name', 'Assigned Panel Prospect')
            ->where('can.start', false)
        );
});
