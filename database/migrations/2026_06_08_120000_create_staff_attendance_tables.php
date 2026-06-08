<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('attendance_date');
            $table->text('reason')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['staff_member_id', 'attendance_date']);
        });

        Schema::create('staff_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('late_override_id')->nullable()->constrained('staff_attendance_overrides')->nullOnDelete();
            $table->date('attendance_date');
            $table->dateTime('clock_in_at')->nullable();
            $table->dateTime('clock_out_at')->nullable();
            $table->string('clock_in_status')->default('on_time');
            $table->string('clock_in_source')->default('self');
            $table->string('clock_out_source')->nullable();
            $table->timestamps();

            $table->unique(['staff_member_id', 'attendance_date']);
            $table->index(['attendance_date', 'clock_in_status']);
        });

        Schema::create('staff_attendance_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('staff_attendance_record_id')->nullable()->constrained('staff_attendance_records')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['staff_member_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_activities');
        Schema::dropIfExists('staff_attendance_records');
        Schema::dropIfExists('staff_attendance_overrides');
    }
};
