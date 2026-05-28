<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('database seeder provisions core seeded users with the default password', function () {
    config()->set('app.super_admin_email', 'admin@ab4irerp.local');

    $this->seed(DatabaseSeeder::class);

    $testUser = User::query()->where('email', 'test@example.com')->firstOrFail();
    $superAdmin = User::query()->where('email', 'admin@ab4irerp.local')->firstOrFail();

    expect(Hash::check('password', $testUser->password))->toBeTrue();
    expect(Hash::check('password', $superAdmin->password))->toBeTrue();
    expect(User::query()->where('email', 'bot.member@ab4irerp.local')->exists())->toBeFalse();
});
