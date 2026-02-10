<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->restrictOnDelete();
            $table->foreignId('sponsor_stakeholder_id')->nullable()->constrained('stakeholders')->nullOnDelete();
            $table->foreignId('project_manager_id')->constrained('staff_members')->restrictOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->enum('status', ['planned', 'active', 'completed', 'on_hold', 'cancelled'])->default('planned');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
