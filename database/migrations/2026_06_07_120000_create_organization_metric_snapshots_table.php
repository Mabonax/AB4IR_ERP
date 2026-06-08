<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_profile_id')->constrained('organization_profiles')->cascadeOnDelete();
            $table->timestamp('captured_at');
            $table->unsignedBigInteger('impact_total')->nullable();
            $table->unsignedBigInteger('impact_digital')->nullable();
            $table->unsignedBigInteger('impact_physical')->nullable();
            $table->unsignedBigInteger('trainings_conducted')->nullable();
            $table->unsignedBigInteger('impact_website')->nullable();
            $table->unsignedBigInteger('impact_walkins')->nullable();
            $table->unsignedBigInteger('impact_facebook')->nullable();
            $table->unsignedBigInteger('impact_x')->nullable();
            $table->unsignedBigInteger('impact_linkedin')->nullable();
            $table->unsignedBigInteger('impact_livestreaming')->nullable();
            $table->unsignedBigInteger('impact_instagram')->nullable();
            $table->unsignedBigInteger('impact_youtube')->nullable();
            $table->timestamps();

            $table->index(['organization_profile_id', 'captured_at'], 'organization_metric_snapshots_profile_captured_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_metric_snapshots');
    }
};
