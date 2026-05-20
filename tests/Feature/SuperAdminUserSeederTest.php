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
    config()->set('app.super_admin_password', 'secret-password');
    config()->set('app.super_admin_sync_password', false);

    $this->seed(AccessControlSeeder::class);
    $this->seed(SuperAdminUserSeeder::class);

    $user = User::query()->where('email', 'bootstrap.admin@example.test')->firstOrFail();

    expect($user->name)->toBe('Bootstrap Admin')
        ->and($user->hasRole('super-admin'))->toBeTrue()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

test('super admin seeder preserves an existing password unless explicit sync is enabled', function () {
    config()->set('app.super_admin_name', 'Bootstrap Admin');
    config()->set('app.super_admin_email', 'bootstrap.admin@example.test');
    config()->set('app.super_admin_password', 'initial-password');
    config()->set('app.super_admin_sync_password', false);

    $this->seed(AccessControlSeeder::class);
    $this->seed(SuperAdminUserSeeder::class);

    config()->set('app.super_admin_password', 'rotated-password');
    $this->seed(SuperAdminUserSeeder::class);

    $user = User::query()->where('email', 'bootstrap.admin@example.test')->firstOrFail();

    expect(Hash::check('initial-password', $user->password))->toBeTrue()
        ->and(Hash::check('rotated-password', $user->password))->toBeFalse();

    config()->set('app.super_admin_sync_password', true);
    $this->seed(SuperAdminUserSeeder::class);

    $user->refresh();

    expect(Hash::check('rotated-password', $user->password))->toBeTrue();
});
