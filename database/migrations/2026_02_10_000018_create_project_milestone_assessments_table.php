<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestone_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_milestone_id')->constrained('project_milestones')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->foreignId('project_location_id')->constrained('project_locations')->cascadeOnDelete();
            $table->foreignId('facilitator_id')->nullable()->constrained('facilitators')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('score')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['project_milestone_id', 'beneficiary_id', 'project_location_id'],
                'pm_assessments_milestone_beneficiary_location_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestone_assessments');
    }
};
