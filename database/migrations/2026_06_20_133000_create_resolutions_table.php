<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('resolution_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();

            $table->unique(['organisation_id', 'resolution_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
