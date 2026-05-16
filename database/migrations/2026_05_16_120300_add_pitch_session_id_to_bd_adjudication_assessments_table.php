<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bd_adjudication_assessments', function (Blueprint $table) {
            $table->foreignId('pitch_session_id')
                ->nullable()
                ->after('smme_id')
                ->constrained('bd_pitch_sessions')
                ->nullOnDelete();

            $table->index(['pitch_session_id', 'smme_id', 'judge_id'], 'bd_adjudication_session_smme_judge_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bd_adjudication_assessments', function (Blueprint $table) {
            $table->dropIndex('bd_adjudication_session_smme_judge_idx');
            $table->dropConstrainedForeignId('pitch_session_id');
        });
    }
};
