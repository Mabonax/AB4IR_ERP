<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makePromotionDepartment(): StaffDepartment
{
    return StaffDepartment::query()->create([
        'name' => 'Operations',
        'description' => 'Operations department',
    ]);
}

function makePromotionStaffUser(
    StaffDepartment $department,
    string $email,
    array $permissions = [],
    array $staffOverrides = [],
): array {
    $user = User::factory()->create([
        'email' => $email,
        'name' => strtok($email, '@'),
    ]);

    $staff = StaffMember::query()->create(array_merge([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => ucfirst(strtok($email, '.')),
        'last_name' => 'Staff',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => false,
        'is_ceo' => false,
        'is_board_member' => false,
    ], $staffOverrides));

    if ($permissions !== []) {
        grantPermissions($user, $permissions);
    }

    return [$user, $staff];
}

test('ceo can promote a staff member to manager', function () {
    $department = makePromotionDepartment();
    [$ceoUser] = makePromotionStaffUser($department, 'ceo.promote@example.test', ['domain.staff.manage'], [
        'is_ceo' => true,
        'is_manager' => true,
    ]);
    [, $staff] = makePromotionStaffUser($department, 'staff.promote@example.test', ['domain.staff.view']);

    $this->actingAs($ceoUser)
        ->post("/staff/{$staff->id}/promote-manager")
        ->assertRedirect()
        ->assertSessionHas('success', 'Staff member promoted to manager');

    expect($staff->fresh()->is_manager)->toBeTrue();
});

test('super admin can promote a staff member to manager', function () {
    $department = makePromotionDepartment();
    [$adminUser] = makePromotionStaffUser($department, 'admin.promote@example.test', ['domain.staff.manage']);
    Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    $adminUser->assignRole('super-admin');

    [, $staff] = makePromotionStaffUser($department, 'staff.superadmin@example.test', ['domain.staff.view']);

    $this->actingAs($adminUser)
        ->post("/staff/{$staff->id}/promote-manager")
        ->assertRedirect();

    expect($staff->fresh()->is_manager)->toBeTrue();
});

test('ordinary staff manager cannot promote another staff member to manager', function () {
    $department = makePromotionDepartment();
    [$managerUser] = makePromotionStaffUser($department, 'manager.noaccess@example.test', ['domain.staff.manage'], [
        'is_manager' => true,
    ]);
    [, $staff] = makePromotionStaffUser($department, 'staff.noaccess@example.test', ['domain.staff.view']);

    $this->actingAs($managerUser)
        ->post("/staff/{$staff->id}/promote-manager")
        ->assertForbidden();

    expect($staff->fresh()->is_manager)->toBeFalse();
});
