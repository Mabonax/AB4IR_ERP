<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'staff_id') || ! Schema::hasColumn('staff_members', 'user_id')) {
            return;
        }

        $linkedStaff = DB::table('staff_members')
            ->select('id', 'user_id')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get();

        foreach ($linkedStaff as $staff) {
            DB::table('users')
                ->where('id', $staff->user_id)
                ->whereNull('staff_id')
                ->update(['staff_id' => $staff->id]);
        }

        $orphanStaff = DB::table('staff_members')
            ->select('id', 'first_name', 'last_name', 'email')
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->orderBy('id')
            ->get();

        foreach ($orphanStaff as $staff) {
            $email = strtolower(trim((string) $staff->email));
            if ($email === '') {
                continue;
            }

            $name = trim(((string) $staff->first_name).' '.((string) $staff->last_name));
            $name = $name !== '' ? $name : $email;

            $existingUser = DB::table('users')
                ->select('id')
                ->where('email', $email)
                ->first();

            $userId = $existingUser?->id;

            if (! $userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make((string) env('STAFF_USER_DEFAULT_PASSWORD', 'password')),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('users')
                    ->where('id', $userId)
                    ->update([
                        'name' => $name,
                        'updated_at' => now(),
                    ]);
            }

            DB::table('staff_members')
                ->where('id', $staff->id)
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);

            DB::table('users')
                ->where('id', $userId)
                ->whereNull('staff_id')
                ->update(['staff_id' => $staff->id]);
        }
    }

    public function down(): void
    {
        // Backfill is intentionally non-reversible.
    }
};

