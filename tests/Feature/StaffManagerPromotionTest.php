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

test('manager staff members can only be assigned to the ceo as their manager', function () {
    $department = makePromotionDepartment();
    [$adminUser] = makePromotionStaffUser($department, 'admin.staff@example.test', ['domain.staff.manage']);
    [, $ceoStaff] = makePromotionStaffUser($department, 'ceo.staff@example.test', [], [
        'is_ceo' => true,
        'is_manager' => true,
    ]);
    [, $departmentManager] = makePromotionStaffUser($department, 'manager.staff@example.test', [], [
        'is_manager' => true,
        'manager_id' => $ceoStaff->id,
    ]);

    $payload = [
        'staff' => [
            'first_name' => 'New',
            'last_name' => 'Manager',
            'email' => 'new.manager@example.test',
            'phone' => '0715550000',
            'employee_number' => 'NEWM-001',
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'department_id' => $department->id,
            'manager_id' => $departmentManager->id,
            'is_ceo' => false,
            'is_board_member' => false,
            'is_manager' => true,
            'is_intern' => false,
        ],
        'next_of_kin' => [
            'full_name' => 'Next Of Kin',
            'relationship' => 'Sibling',
            'phone' => '0715550001',
            'email' => 'nok@example.test',
        ],
    ];

    $this->actingAs($adminUser)
        ->post('/staff', $payload)
        ->assertSessionHasErrors(['staff.manager_id']);

    $payload['staff']['manager_id'] = $ceoStaff->id;
    $payload['staff']['email'] = 'new.manager.ceo@example.test';
    $payload['staff']['employee_number'] = 'NEWM-002';

    $this->actingAs($adminUser)
        ->post('/staff', $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $created = StaffMember::query()->where('email', 'new.manager.ceo@example.test')->firstOrFail();

    expect((int) $created->manager_id)->toBe((int) $ceoStaff->id)
        ->and($created->is_manager)->toBeTrue();
});
