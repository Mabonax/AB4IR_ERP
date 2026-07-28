<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->foreignId('committee_id')->nullable()->constrained('committees')->nullOnDelete();
            $table->string('meeting_number');
            $table->string('title');
            $table->date('meeting_date');
            $table->string('location')->nullable();
            $table->longText('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['organisation_id', 'meeting_number']);
        });

        Schema::create('meeting_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('attendance_status');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendance');
        Schema::dropIfExists('meetings');
    }
};
