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
        $name = (string) config('app.super_admin_name', 'Super Admin');
        $email = (string) config('app.super_admin_email', 'admin@ab4irerp.local');
        $password = (string) config('app.super_admin_password', 'password');

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
