<?php

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\AttendanceEntry;
use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Models\NextOfKin;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('program show page renders a program-wide overview with associated projects and yearly stats', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'programs');

    $program = Program::query()->create([
        'title' => 'Enterprise Growth Programme',
        'description' => 'Program-wide reporting view test',
        'slug' => 'enterprise-growth-programme',
    ]);

    ProgramMilestoneTemplate::query()->create([
        'program_id' => $program->id,
        'title' => 'Pitch readiness',
        'description' => 'Foundational delivery milestone',
        'sort_order' => 1,
        'max_score' => 100,
    ]);

    $project2024 = Project::query()->create([
        'program_id' => $program->id,
        'name' => 'Enterprise Cohort 2024',
        'start_date' => '2024-02-01',
        'end_date' => '2024-11-30',
        'status' => 'completed',
        'description' => 'Completed program iteration',
    ]);

    $project2025 = Project::query()->create([
        'program_id' => $program->id,
        'name' => 'Enterprise Cohort 2025',
        'start_date' => '2025-02-01',
        'end_date' => '2025-11-30',
        'status' => 'active',
        'description' => 'Active program iteration',
    ]);

    $province2024 = Provinces::query()->create(['name' => 'Gauteng '.Str::upper(Str::random(4))]);
    $province2025 = Provinces::query()->create(['name' => 'Limpopo '.Str::upper(Str::random(4))]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Faith',
        'surname' => 'Mokoena',
        'dob' => now()->subYears(30)->toDateString(),
        'id_number' => fake()->unique()->numerify('####################'),
        'address' => '12 Training Road',
        'email' => 'facilitator-'.Str::lower(Str::random(6)).'@example.com',
        'cell' => '073'.random_int(1000000, 9999999),
        'specialization' => 'Training',
        'province_id' => $province2024->id,
    ]);

    $location2024 = ProjectLocation::query()->create([
        'project_id' => $project2024->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province2024->id,
        'training_venue_address' => 'Johannesburg Hub',
    ]);

    $location2025 = ProjectLocation::query()->create([
        'project_id' => $project2025->id,
        'facilitator_id' => $facilitator->id,
        'province_id' => $province2025->id,
        'training_venue_address' => 'Pretoria Hub',
    ]);

    $nextOfKin = NextOfKin::query()->create([
        'name' => 'Nomsa',
        'surname' => 'Nkosi',
        'relationship' => 'Sibling',
        'phone' => '074'.random_int(1000000, 9999999),
        'email' => 'nok-'.Str::lower(Str::random(6)).'@example.com',
    ]);

    $beneficiary2024 = Beneficiary::query()->create([
        'name' => 'Lebo',
        'surname' => 'Nkosi',
        'dob' => now()->subYears(24),
        'age' => 24,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'lebo-'.Str::lower(Str::random(6)).'@example.com',
        'phone' => '071'.random_int(1000000, 9999999),
        'project_id' => $project2024->id,
        'attendance_status' => 'active',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    $beneficiary2025 = Beneficiary::query()->create([
        'name' => 'Anele',
        'surname' => 'Dlamini',
        'dob' => now()->subYears(26),
        'age' => 26,
        'id_number' => fake()->unique()->numerify('#############'),
        'email' => 'anele-'.Str::lower(Str::random(6)).'@example.com',
        'phone' => '072'.random_int(1000000, 9999999),
        'project_id' => $project2025->id,
        'attendance_status' => 'dropout',
        'next_of_kin_id' => $nextOfKin->id,
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $project2024->id,
        'project_location_id' => $location2024->id,
        'beneficiary_id' => $beneficiary2024->id,
        'status' => 'completed',
        'enrolled_at' => now()->subYear(),
    ]);

    ProjectEnrollment::query()->create([
        'project_id' => $project2025->id,
        'project_location_id' => $location2025->id,
        'beneficiary_id' => $beneficiary2025->id,
        'status' => 'dropped',
        'enrolled_at' => now(),
    ]);

    $register = AttendanceRegister::query()->create([
        'project_id' => $project2024->id,
        'project_location_id' => $location2024->id,
        'facilitator_id' => $facilitator->id,
        'attendance_date' => '2024-03-01',
        'is_holiday' => false,
    ]);

    AttendanceEntry::query()->create([
        'attendance_register_id' => $register->id,
        'beneficiary_id' => $beneficiary2024->id,
        'status' => 'present',
    ]);

    $this->actingAs($user)
        ->get("/programs/{$program->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Programs/Show')
            ->where('program.data.id', $program->id)
            ->where('documentRepository', null)
            ->where('stats.total_projects', 2)
            ->where('stats.total_locations', 2)
            ->where('stats.unique_beneficiaries', 2)
            ->where('stats.active_projects', 1)
            ->where('stats.completed_projects', 1)
            ->has('yearlyImpact', 2)
            ->where('yearlyImpact.0.year', '2024')
            ->where('yearlyImpact.1.year', '2025')
            ->has('projects', 2)
            ->where('projects.0.name', 'Enterprise Cohort 2024')
        );
});
