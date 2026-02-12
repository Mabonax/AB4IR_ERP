<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilitators', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill legacy facilitators by matching email to existing users.
        $rows = DB::table('facilitators')
            ->select('id', 'email')
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->get();

        foreach ($rows as $row) {
            $userId = DB::table('users')
                ->where('email', $row->email)
                ->value('id');

            if ($userId) {
                DB::table('facilitators')
                    ->where('id', $row->id)
                    ->update(['user_id' => $userId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('facilitators', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('facilitators_user_id_unique');
            $table->dropColumn('user_id');
        });
    }
};
