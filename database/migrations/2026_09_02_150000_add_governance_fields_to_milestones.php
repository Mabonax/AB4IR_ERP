<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_milestone_templates', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('max_score');
            $table->boolean('is_active')->default(true)->after('is_required');
            $table->unsignedInteger('pass_mark')->nullable()->after('is_active');
            $table->string('expected_timing')->nullable()->after('pass_mark');
        });

        Schema::table('project_milestones', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('max_score');
            $table->boolean('is_active')->default(true)->after('is_required');
            $table->unsignedInteger('pass_mark')->nullable()->after('is_active');
            $table->string('expected_timing')->nullable()->after('pass_mark');
        });
    }

    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropColumn(['is_required', 'is_active', 'pass_mark', 'expected_timing']);
        });

        Schema::table('program_milestone_templates', function (Blueprint $table) {
            $table->dropColumn(['is_required', 'is_active', 'pass_mark', 'expected_timing']);
        });
    }
};
