<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_closure_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->json('attendance_summary')->nullable();
            $table->json('registration_summary')->nullable();
            $table->longText('budget_summary')->nullable();
            $table->longText('outcomes_achieved')->nullable();
            $table->longText('lessons_learned')->nullable();
            $table->longText('risks_encountered')->nullable();
            $table->longText('recommendations')->nullable();
            $table->text('closure_reason')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_closure_reports');
    }
};
