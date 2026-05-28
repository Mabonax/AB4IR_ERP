<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('super user can sync roles and direct permissions to another user', function () {
    $admin = User::factory()->create();
    $superRole = Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    $admin->assignRole($superRole);

    $managedUser = User::factory()->create();

    Role::create([
        'name' => 'report-viewer',
        'guard_name' => 'web',
    ]);

    Permission::create([
        'name' => 'reports.export',
        'guard_name' => 'web',
    ]);

    $this->actingAs($admin)
        ->post(route('access-control.users.roles.sync', $managedUser), [
            'roles' => ['report-viewer'],
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'User roles updated successfully.');

    $this->actingAs($admin)
        ->post(route('access-control.users.permissions.sync', $managedUser), [
            'permissions' => ['reports.export'],
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'User direct permissions updated successfully.');

    expect($managedUser->fresh()->hasRole('report-viewer'))->toBeTrue();
    expect($managedUser->fresh()->hasDirectPermission('reports.export'))->toBeTrue();
});

test('non super user cannot access access control even with legacy permissions', function () {
    $manager = User::factory()->create();
    grantPermissions($manager, [
        'access-control.view',
        'roles.update',
        'permissions.update',
        'assignments.manage',
    ]);

    $role = Role::create([
        'name' => 'field-operator',
        'guard_name' => 'web',
    ]);

    Permission::create([
        'name' => 'field.data.export',
        'guard_name' => 'web',
    ]);

    $this->actingAs($manager)
        ->get(route('access-control.roles.index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->put(route('access-control.roles.update', $role), [
            'name' => 'field-operator-updated',
            'permissions' => [],
        ])
        ->assertForbidden();

    $permission = Permission::query()->where('name', 'field.data.export')->firstOrFail();

    $this->actingAs($manager)
        ->put(route('access-control.permissions.update', $permission), [
            'name' => 'field.data.export.updated',
        ])
        ->assertForbidden();
});

test('super user can update roles and permissions through put routes', function () {
    $superUser = User::factory()->create();
    $superRole = Role::firstOrCreate([
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    $superUser->assignRole($superRole);

    $permission = Permission::create([
        'name' => 'reports.archive',
        'guard_name' => 'web',
    ]);

    $role = Role::create([
        'name' => 'report-auditor',
        'guard_name' => 'web',
    ]);

    $this->actingAs($superUser)
        ->put(route('access-control.roles.update', $role), [
            'name' => 'report-auditor-updated',
            'permissions' => ['reports.archive'],
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Role updated successfully.');

    $this->actingAs($superUser)
        ->put(route('access-control.permissions.update', $permission), [
            'name' => 'reports.archive.download',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Permission updated successfully.');

    expect($role->fresh()->name)->toBe('report-auditor-updated');
    expect($permission->fresh()->name)->toBe('reports.archive.download');
    expect($role->fresh()->hasPermissionTo('reports.archive.download'))->toBeTrue();
});

test('access control seeder creates marketing permissions and assigns them through marketing roles', function () {
    StaffDepartment::query()->create([
        'name' => 'Marketing',
        'description' => 'Marketing department',
    ]);

    $this->seed(AccessControlSeeder::class);

    $marketingView = Permission::query()->where('name', 'domain.marketing.view')->first();
    $marketingManage = Permission::query()->where('name', 'domain.marketing.manage')->first();
    $marketingUserRole = Role::query()->where('name', 'department-user-marketing')->first();
    $marketingManagerRole = Role::query()->where('name', 'department-manager-marketing')->first();

    expect($marketingView)->not->toBeNull()
        ->and($marketingManage)->not->toBeNull()
        ->and($marketingUserRole)->not->toBeNull()
        ->and($marketingManagerRole)->not->toBeNull()
        ->and($marketingUserRole->hasPermissionTo('domain.marketing.view'))->toBeTrue()
        ->and($marketingUserRole->hasPermissionTo('domain.marketing.manage'))->toBeFalse()
        ->and($marketingManagerRole->hasPermissionTo('domain.marketing.view'))->toBeTrue()
        ->and($marketingManagerRole->hasPermissionTo('domain.marketing.manage'))->toBeTrue()
        ->and($marketingUserRole->hasPermissionTo('domain.task-management.view'))->toBeTrue()
        ->and($marketingManagerRole->hasPermissionTo('domain.task-management.manage'))->toBeTrue();
});
