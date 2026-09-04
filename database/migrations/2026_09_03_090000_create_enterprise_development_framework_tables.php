<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_development_dimensions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->decimal('weighting', 8, 2)->default(1);
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enterprise_development_criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dimension_id')->constrained('enterprise_development_dimensions')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->decimal('weighting', 8, 2)->default(1);
            $table->boolean('required')->default(false);
            $table->boolean('active')->default(true);
            $table->boolean('evidence_required')->default(false);
            $table->text('guidance')->nullable();
            $table->boolean('expires')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dimension_id', 'active', 'sequence'], 'ed_criteria_dimension_active_seq_idx');
        });

        Schema::create('enterprise_diagnostics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bds_incubatee_id')->constrained('bds_incubatees')->restrictOnDelete();
            $table->string('assessment_type');
            $table->date('assessment_date');
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('dimension_scores')->nullable();
            $table->json('outcome_baseline')->nullable();
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bds_incubatee_id', 'assessment_type', 'assessment_date'], 'ed_diagnostic_unique_day_type');
            $table->index(['bds_incubatee_id', 'assessment_type', 'status'], 'ed_diagnostics_incubatee_type_status_idx');
        });

        Schema::create('enterprise_diagnostic_criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enterprise_diagnostic_id')->constrained('enterprise_diagnostics')->cascadeOnDelete();
            $table->foreignId('criterion_id')->nullable()->constrained('enterprise_development_criteria')->nullOnDelete();
            $table->foreignId('dimension_id')->nullable()->constrained('enterprise_development_dimensions')->nullOnDelete();
            $table->string('criterion_code');
            $table->string('criterion_name');
            $table->string('dimension_code');
            $table->string('dimension_name');
            $table->decimal('criterion_weighting', 8, 2)->default(1);
            $table->decimal('dimension_weighting', 8, 2)->default(1);
            $table->boolean('evidence_required')->default(false);
            $table->boolean('required')->default(false);
            $table->string('maturity_status')->default('not_assessed');
            $table->unsignedTinyInteger('maturity_score')->nullable();
            $table->text('assessor_observation')->nullable();
            $table->foreignId('evidence_document_file_id')->nullable()->constrained('document_files')->nullOnDelete();
            $table->string('evidence_label')->nullable();
            $table->date('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['enterprise_diagnostic_id', 'criterion_code'], 'ed_diagnostic_criterion_unique');
            $table->index(['dimension_code', 'maturity_status'], 'ed_criteria_dimension_status_idx');
        });

        Schema::create('enterprise_development_gaps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enterprise_diagnostic_id')->constrained('enterprise_diagnostics')->cascadeOnDelete();
            $table->foreignId('bds_incubatee_id')->constrained('bds_incubatees')->restrictOnDelete();
            $table->foreignId('criterion_result_id')->nullable()->constrained('enterprise_diagnostic_criteria')->nullOnDelete();
            $table->string('dimension_code');
            $table->string('dimension_name');
            $table->string('criterion_code');
            $table->string('criterion_name');
            $table->string('severity')->default('medium');
            $table->text('reason')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->index(['bds_incubatee_id', 'status', 'severity'], 'ed_gaps_incubatee_status_severity_idx');
        });

        Schema::create('enterprise_development_needs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bds_incubatee_id')->constrained('bds_incubatees')->restrictOnDelete();
            $table->foreignId('enterprise_diagnostic_id')->nullable()->constrained('enterprise_diagnostics')->nullOnDelete();
            $table->foreignId('development_gap_id')->nullable()->constrained('enterprise_development_gaps')->nullOnDelete();
            $table->string('title');
            $table->string('dimension_code')->nullable();
            $table->string('dimension_name')->nullable();
            $table->string('priority')->default('medium');
            $table->text('reason')->nullable();
            $table->string('source')->default('diagnostic');
            $table->string('status')->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bds_incubatee_id', 'status', 'priority'], 'ed_needs_incubatee_status_priority_idx');
        });

        Schema::create('enterprise_development_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bds_incubatee_id')->constrained('bds_incubatees')->restrictOnDelete();
            $table->foreignId('baseline_diagnostic_id')->nullable()->constrained('enterprise_diagnostics')->nullOnDelete();
            $table->string('title');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('enterprise_development_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('development_plan_id')->constrained('enterprise_development_plans')->cascadeOnDelete();
            $table->foreignId('development_need_id')->nullable()->constrained('enterprise_development_needs')->nullOnDelete();
            $table->string('objective');
            $table->string('priority')->default('medium');
            $table->date('target_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['development_plan_id', 'status', 'priority'], 'ed_plan_items_plan_status_priority_idx');
        });

        Schema::create('enterprise_development_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bds_incubatee_id')->constrained('bds_incubatees')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('title');
            $table->text('details')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['bds_incubatee_id', 'occurred_at'], 'ed_history_incubatee_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_development_history');
        Schema::dropIfExists('enterprise_development_plan_items');
        Schema::dropIfExists('enterprise_development_plans');
        Schema::dropIfExists('enterprise_development_needs');
        Schema::dropIfExists('enterprise_development_gaps');
        Schema::dropIfExists('enterprise_diagnostic_criteria');
        Schema::dropIfExists('enterprise_diagnostics');
        Schema::dropIfExists('enterprise_development_criteria');
        Schema::dropIfExists('enterprise_development_dimensions');
    }
};
