<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_register_id')->constrained('attendance_registers')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'excused'])->default('present');
            $table->string('excused_reason')->nullable();
            $table->timestamps();

            $table->unique(['attendance_register_id', 'beneficiary_id'], 'attendance_register_beneficiary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_entries');
    }
};
