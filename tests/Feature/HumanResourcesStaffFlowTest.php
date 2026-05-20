<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('human resources dashboard exposes department staff actions', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'human-resources');
    grantDomainAccess($user, 'staff');

    $department = StaffDepartment::query()->create([
        'name' => 'Operations',
        'description' => 'Operations team',
    ]);

    $manager = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Olive',
        'last_name' => 'Manager',
        'email' => 'olive.manager@example.test',
        'employee_number' => 'OPS-001',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    StaffMember::query()->create([
        'department_id' => $department->id,
        'manager_id' => $manager->id,
        'first_name' => 'Nina',
        'last_name' => 'Staff',
        'email' => 'nina.staff@example.test',
        'employee_number' => 'OPS-002',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get('/human-resources')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanResources/Dashboard')
            ->where('departments.0.name', 'Operations')
            ->where('departments.0.staff_count', 2)
            ->has('managers', 2)
        );
});

test('staff create page supports department scoped onboarding flow', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'staff');

    $department = StaffDepartment::query()->create([
        'name' => 'Technical Support',
        'description' => 'Support desk',
    ]);

    $this->actingAs($user)
        ->get("/staff/create?department_id={$department->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Staff/Create')
            ->where('selectedDepartmentId', $department->id)
        );
});

test('staff resource exposes internship details and derived duration', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'staff');

    $department = StaffDepartment::query()->create([
        'name' => 'Internship Office',
        'description' => 'Youth placement desk',
    ]);

    $staff = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Lebo',
        'last_name' => 'Intern',
        'email' => 'lebo.intern@example.test',
        'employee_number' => 'INT-100',
        'start_date' => '2026-05-01',
        'status' => 'active',
        'is_intern' => true,
        'intern_sponsor_name' => 'YES Youth',
        'internship_start_date' => '2026-05-01',
        'internship_end_date' => '2026-08-31',
    ]);

    $this->actingAs($user)
        ->get("/staff/{$staff->id}")
        ->assertOk()
        ->assertJsonPath('is_intern', true)
        ->assertJsonPath('intern_sponsor_name', 'YES Youth')
        ->assertJsonPath('internship_start_date', '2026-05-01')
        ->assertJsonPath('internship_end_date', '2026-08-31')
        ->assertJsonPath('internship_duration.days', 123);
});
