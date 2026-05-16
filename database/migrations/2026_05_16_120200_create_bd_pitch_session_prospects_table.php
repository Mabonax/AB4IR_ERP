<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd_pitch_session_prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_session_id')->constrained('bd_pitch_sessions')->cascadeOnDelete();
            $table->foreignId('bds_application_id')->constrained('bds_applications')->cascadeOnDelete();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->unsignedInteger('consolidated_total_score')->default(0);
            $table->unsignedInteger('submitted_assessments_count')->default(0);
            $table->enum('manager_decision', ['incubated', 'rejected'])->nullable();
            $table->timestamp('manager_decided_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamps();

            $table->unique(['pitch_session_id', 'bds_application_id'], 'bd_pitch_session_application_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_pitch_session_prospects');
    }
};
