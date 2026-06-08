<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Staff\Notifications\StaffSystemAccessNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

test('staff onboarding provisions a linked user with the configured default password', function () {
    config()->set('staff.default_password', 'TempPass123!');
    config()->set('staff.send_welcome_notification', true);
    Notification::fake();

    $user = User::factory()->create();
    grantDomainAccess($user, 'staff');

    $department = StaffDepartment::query()->create([
        'name' => 'Operations',
        'description' => 'Operations team',
    ]);

    $this->actingAs($user)
        ->post('/staff', [
            'staff.first_name' => 'Alicia',
            'staff.last_name' => 'Mokoena',
            'staff.email' => 'alicia.mokoena@example.test',
            'staff.phone' => '0710000000',
            'staff.employee_number' => 'OPS-900',
            'staff.start_date' => '2026-05-20',
            'staff.status' => 'active',
            'staff.department_id' => $department->id,
            'next_of_kin.full_name' => 'Neo Mokoena',
            'next_of_kin.relationship' => 'Sibling',
            'next_of_kin.phone' => '0720000000',
            'next_of_kin.email' => 'neo.mokoena@example.test',
        ])
        ->assertSessionHasNoErrors();

    $staff = StaffMember::query()->where('email', 'alicia.mokoena@example.test')->firstOrFail();
    $linkedUser = User::query()->where('email', 'alicia.mokoena@example.test')->firstOrFail();

    expect($staff->user_id)->toBe($linkedUser->id);
    expect($linkedUser->staff_id)->toBe($staff->id);
    expect(Hash::check('TempPass123!', $linkedUser->password))->toBeTrue();
    Notification::assertSentTo($linkedUser, StaffSystemAccessNotification::class);
});

test('human resources dashboard filters the staff directory by department', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'human-resources');
    grantDomainAccess($user, 'staff');

    $technical = StaffDepartment::query()->create([
        'name' => 'Technical',
        'description' => 'Technical team',
    ]);

    $marketing = StaffDepartment::query()->create([
        'name' => 'Marketing',
        'description' => 'Marketing team',
    ]);

    StaffMember::query()->create([
        'department_id' => $technical->id,
        'first_name' => 'Tina',
        'last_name' => 'Tech',
        'email' => 'tina.tech@example.test',
        'employee_number' => 'TECH-101',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    StaffMember::query()->create([
        'department_id' => $marketing->id,
        'first_name' => 'Mark',
        'last_name' => 'Eter',
        'email' => 'mark.eter@example.test',
        'employee_number' => 'MKT-101',
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get('/human-resources?department_id='.$technical->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanResources/Dashboard')
            ->where('selectedDepartmentId', $technical->id)
            ->has('staffDirectory', 1)
            ->where('staffDirectory.0.department_name', 'Technical')
        );
});
