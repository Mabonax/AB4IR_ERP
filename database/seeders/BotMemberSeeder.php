<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BotMemberSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('access_control.guard', 'web');
        $name = (string) config('app.bot_member_name', 'Bot Member');
        $email = (string) config('app.bot_member_email', 'bot.member@ab4irerp.local');
        $password = (string) config('app.bot_member_password', 'password');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        Role::firstOrCreate([
            'name' => 'bot-member',
            'guard_name' => $guard,
        ]);

        $user->syncRoles(['bot-member']);
    }
}
