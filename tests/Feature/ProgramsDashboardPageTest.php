<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\AttendanceEntry;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Models\NextOfKin;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('programs dashboard renders portfolio program statistics and drill-down data', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'programs');

    $programA = Program::query()->create([
        'title' => 'Enterprise Growth',
        'description' => 'Enterprise growth track',
        'slug' => 'enterprise-growth',
    ]);

    $programB = Program::query()->create([
        'title' => 'Youth Activation',
        'description' => 'Youth activation track',
        'slug' => 'youth-activation',
    ]);

    $province = Provinces::query()->create(['name' => 'North West '.Str::upper(Str::random(4))]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Thato',
        'surname' => 'Molefe',
        'dob' => now()->subYears(31)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '45 Market Street',
        'email' => 'facilitator-'.Str::lower(Str::random(6)).'@example.com',
        'cell' => '078'.random_int(1000000, 9999999),
        'specialization' => 'Facilitation',
        'province_id' => $province->id,
    ]);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Mpho',
        'surname' => 'Molefe',
        'relationship' => 'Parent',
        'phone' => '079'.random_int(1000000, 9999999),
        'email' => 'nok-'.Str::lower(Str::random(6)).'@example.com',
    ]);

    $projectA = Project::query()->create([
        'program_id' => $programA->id,
        'name' => 'Enterprise Cohort A',
        'start_date' => '2024-01-01',
        'status' => 'active',
        'description' => 'Program A project',
    ]);

    $projectB = Project::query()->create([
        'program_id' => $programB->id,
        'name' => 'Youth Cohort B',
        'start_date' => '2025-01-01',
        'status' => 'completed',
        'description' => 'Program B project',
    ]);

    $locationA = ProjectLocation::query()->create([
        'project_id' => $projectA->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Venue A',
    ]);

    $locationB = ProjectLocation::query()->create([
        'project_id' => $projectB->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province->id,
        'training_venue_address' => 'Venue B',
    ]);

    $beneficiaryA = Beneficiary::query()->create([
        'name' => 'Ayanda',
        'surname' => 'Dube',
        'dob' => now()->subYears(24),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'ayanda-'.Str::lower(Str::random(6)).'@example.com',
        'phone' => '071'.random_int(1000000, 9999999),
        'project_id' => $projectA->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    $beneficiaryB = Beneficiary::query()->create([
        'name' => 'Sipho',
        'surname' => 'Zulu',
        'dob' => now()->subYears(25),
        'age' => 25,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'sipho-'.Str::lower(Str::random(6)).'@example.com',
        'phone' => '072'.random_int(1000000, 9999999),
        'project_id' => $projectB->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $projectA->id,
        'project_location_id' => $locationA->id,
        'beneficiary_id' => $beneficiaryA->id,
        'status' => 'enrolled',
        'enrolled_at' => now(),
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $projectB->id,
        'project_location_id' => $locationB->id,
        'beneficiary_id' => $beneficiaryB->id,
        'status' => 'completed',
        'enrolled_at' => now(),
    ]);

    $register = AttendanceRegister::query()->create([
        'project_id' => $projectA->id,
        'project_location_id' => $locationA->id,
        'facilitator_id' => $facilitator->id,
        'attendance_date' => '2024-02-01',
        'is_holiday' => false,
    ]);

    AttendanceEntry::query()->create([
        'attendance_register_id' => $register->id,
        'beneficiary_id' => $beneficiaryA->id,
        'status' => 'present',
    ]);

    $this->actingAs($user)
        ->get('/programs')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Programs/Dashboard')
            ->where('stats.tracked_programs', 2)
            ->where('stats.active_projects', 1)
            ->where('stats.completed_projects', 1)
            ->where('stats.total_locations', 2)
            ->has('programs', 2)
            ->where('programs.0.title', 'Enterprise Growth')
        );
});

test('programs list route redirects to the dashboard to avoid a duplicate surface', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'programs');

    $program = Program::query()->create([
        'title' => 'Operations Support',
        'description' => 'Managed from the list page',
        'slug' => 'operations-support',
    ]);

    $this->actingAs($user)
        ->get('/programs/list')
        ->assertRedirect('/programs');
});
