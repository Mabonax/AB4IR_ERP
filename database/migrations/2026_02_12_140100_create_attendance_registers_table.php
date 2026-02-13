<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_location_id')->constrained('project_locations')->cascadeOnDelete();
            $table->foreignId('facilitator_id')->nullable()->constrained('facilitators')->nullOnDelete();
            $table->date('attendance_date');
            $table->boolean('is_holiday')->default(false);
            $table->string('holiday_reason')->nullable();
            $table->foreignId('holiday_marked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_location_id', 'attendance_date'], 'attendance_location_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_registers');
    }
};
