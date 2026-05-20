<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_workstreams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('event_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_workstream_id')->constrained('event_workstreams')->cascadeOnDelete();
            $table->enum('phase', ['pre_event', 'preparations', 'event_day', 'post_event'])->default('pre_event');
            $table->string('duty');
            $table->date('due_date')->nullable();
            $table->string('responsible_person')->nullable();
            $table->string('outcome')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'on_going', 'blocked', 'cancelled'])->default('pending');
            $table->text('comment')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_tasks');
        Schema::dropIfExists('event_workstreams');
    }
};
