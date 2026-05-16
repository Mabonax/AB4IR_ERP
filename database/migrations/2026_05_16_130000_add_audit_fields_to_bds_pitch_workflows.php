<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bd_pitch_sessions', function (Blueprint $table): void {
            $table->timestamp('panel_locked_at')->nullable()->after('started_at');
            $table->foreignId('panel_locked_by')->nullable()->after('panel_locked_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('bd_pitch_session_prospects', function (Blueprint $table): void {
            $table->timestamp('consolidated_at')->nullable()->after('submitted_assessments_count');
            $table->foreignId('consolidated_by')->nullable()->after('consolidated_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('bd_adjudication_assessments', function (Blueprint $table): void {
            $table->json('submitted_snapshot')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('bd_adjudication_assessments', function (Blueprint $table): void {
            $table->dropColumn('submitted_snapshot');
        });

        Schema::table('bd_pitch_session_prospects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('consolidated_by');
            $table->dropColumn('consolidated_at');
        });

        Schema::table('bd_pitch_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('panel_locked_by');
            $table->dropColumn('panel_locked_at');
        });
    }
};
