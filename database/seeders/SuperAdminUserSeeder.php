<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminUserSeeder extends Seeder
{
    protected const DEFAULT_SEEDED_PASSWORD = 'password';

    public function run(): void
    {
        $guard = config('access_control.guard', 'web');
        $name = (string) config('app.super_admin_name', 'Super Admin');
        $email = (string) config('app.super_admin_email', 'admin@poa.org.za');
        $syncPassword = (bool) config('app.super_admin_sync_password', false);

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->name = $name;
        $user->email_verified_at = $user->email_verified_at ?? now();

        if (! $user->exists || $syncPassword) {
            $user->password = Hash::make(self::DEFAULT_SEEDED_PASSWORD);
        }

        $user->save();

        Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => $guard,
        ]);

        $user->syncRoles(['super-admin']);
    }
}
