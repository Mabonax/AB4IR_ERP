<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bds_kpi_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category')->default('growth');
            $table->string('measurement_type')->default('number');
            $table->string('unit')->nullable();
            $table->decimal('default_target_value', 12, 2)->nullable();
            $table->unsignedInteger('weight')->default(1);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['name', 'category']);
        });

        Schema::create('bds_incubatee_kpis', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bds_incubatee_id')->constrained('bds_incubatees')->cascadeOnDelete();
            $table->foreignId('bds_kpi_definition_id')->constrained('bds_kpi_definitions')->restrictOnDelete();
            $table->decimal('target_value', 12, 2)->nullable();
            $table->decimal('baseline_value', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bds_incubatee_id', 'bds_kpi_definition_id']);
        });

        Schema::create('bds_incubatee_kpi_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bds_incubatee_kpi_id')->constrained('bds_incubatee_kpis')->cascadeOnDelete();
            $table->date('review_date');
            $table->decimal('actual_value', 12, 2)->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('status')->default('on_track');
            $table->text('evidence_notes')->nullable();
            $table->text('mentor_comments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bds_incubatee_kpi_reviews');
        Schema::dropIfExists('bds_incubatee_kpis');
        Schema::dropIfExists('bds_kpi_definitions');
    }
};
