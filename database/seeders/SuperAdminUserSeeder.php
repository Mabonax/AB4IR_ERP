<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('access_control.guard', 'web');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');
        $email = env('SUPER_ADMIN_EMAIL', 'admin@ab4irerp.local');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('super-admin')) {
            Role::firstOrCreate([
                'name' => 'super-admin',
                'guard_name' => $guard,
            ]);

            $user->assignRole('super-admin');
        }
    }
}
