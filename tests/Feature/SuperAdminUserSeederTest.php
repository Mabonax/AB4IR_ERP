<?php

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\SuperAdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('super admin seeder creates the bootstrap super admin user and assigns the role', function () {
    config()->set('app.super_admin_name', 'Bootstrap Admin');
    config()->set('app.super_admin_email', 'bootstrap.admin@example.test');
    config()->set('app.super_admin_sync_password', false);

    $this->seed(AccessControlSeeder::class);
    $this->seed(SuperAdminUserSeeder::class);

    $user = User::query()->where('email', 'bootstrap.admin@example.test')->firstOrFail();

    expect($user->name)->toBe('Bootstrap Admin')
        ->and($user->hasRole('super-admin'))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('super admin seeder preserves an existing password unless explicit sync is enabled', function () {
    config()->set('app.super_admin_name', 'Bootstrap Admin');
    config()->set('app.super_admin_email', 'bootstrap.admin@example.test');
    config()->set('app.super_admin_sync_password', false);

    $this->seed(AccessControlSeeder::class);
    $this->seed(SuperAdminUserSeeder::class);

    $user = User::query()->where('email', 'bootstrap.admin@example.test')->firstOrFail();
    $user->forceFill(['password' => Hash::make('changed-in-app')])->save();

    $this->seed(SuperAdminUserSeeder::class);

    $user->refresh();

    expect(Hash::check('changed-in-app', $user->password))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeFalse();

    config()->set('app.super_admin_sync_password', true);
    $this->seed(SuperAdminUserSeeder::class);

    $user->refresh();

    expect(Hash::check('password', $user->password))->toBeTrue();
});
