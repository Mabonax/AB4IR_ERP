<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('is_intern')->default(false)->after('is_manager');
            $table->string('intern_sponsor_name')->nullable()->after('is_intern');
            $table->date('internship_start_date')->nullable()->after('intern_sponsor_name');
            $table->date('internship_end_date')->nullable()->after('internship_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn([
                'is_intern',
                'intern_sponsor_name',
                'internship_start_date',
                'internship_end_date',
            ]);
        });
    }
};
