<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('closure_date');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('concluded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('signoff_notes')->nullable();
            $table->text('final_report_summary')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique('project_id');
        });

        Schema::create('project_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_closure_id')->nullable()->constrained('project_closures')->nullOnDelete();
            $table->string('report_type', 20);
            $table->string('title');
            $table->date('report_date');
            $table->text('executive_summary')->nullable();
            $table->text('key_findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('snapshot')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reports');
        Schema::dropIfExists('project_closures');
    }
};
