<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('attendance_type')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn(['surname', 'attendance_type']);
        });
    }
};
