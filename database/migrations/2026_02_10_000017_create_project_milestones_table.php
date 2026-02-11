<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('program_milestone_template_id')
                ->constrained('program_milestone_templates')
                ->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('max_score')->nullable();
            $table->timestamps();

            $table->unique(
                ['project_id', 'program_milestone_template_id'],
                'pm_proj_tmpl_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
