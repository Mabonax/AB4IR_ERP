<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_learning_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('lms_offering_id', 64);
            $table->string('status', 32)->default('active');
            $table->json('offering_snapshot')->nullable();
            $table->timestamp('mapped_at')->nullable();
            $table->foreignId('mapped_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'lms_offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_learning_mappings');
    }
};
