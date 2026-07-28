<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('database seeder provisions core seeded users with the default password', function () {
    config()->set('app.super_admin_email', 'admin@poa.org.za');

    $this->seed(DatabaseSeeder::class);

    $testUser = User::query()->where('email', 'test@example.com')->firstOrFail();
    $superAdmin = User::query()->where('email', 'admin@poa.org.za')->firstOrFail();

    expect(Hash::check('password', $testUser->password))->toBeTrue();
    expect(Hash::check('password', $superAdmin->password))->toBeTrue();
    expect(User::query()->where('email', 'bot.member@poa.org.za')->exists())->toBeFalse();
});
