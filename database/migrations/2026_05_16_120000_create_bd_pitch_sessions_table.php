<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd_pitch_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('scheduled_for');
            $table->string('venue')->nullable();
            $table->unsignedInteger('expected_prospect_count')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'consolidated', 'approved', 'cancelled'])->default('scheduled');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('consolidated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_pitch_sessions');
    }
};
