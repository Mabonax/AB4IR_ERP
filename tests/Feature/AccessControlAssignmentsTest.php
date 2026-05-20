<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('authorized user can sync roles and direct permissions to another user', function () {
    $admin = User::factory()->create();
    grantPermissions($admin, ['access-control.view', 'assignments.manage']);

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
