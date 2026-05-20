<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('category');
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('topic')->nullable();
            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->string('attendance_status')->default('registered');
            $table->timestamp('checked_in_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['event_id', 'category']);
            $table->index(['event_id', 'attendance_status']);
        });

        $speakerRows = DB::table('event_speakers')->get();
        foreach ($speakerRows as $speaker) {
            DB::table('event_participants')->insert([
                'event_id' => $speaker->event_id,
                'category' => 'speaker',
                'name' => $speaker->name,
                'title' => $speaker->title,
                'organization_name' => $speaker->organization_name,
                'topic' => $speaker->topic,
                'bio' => $speaker->bio,
                'email' => $speaker->email,
                'phone' => $speaker->phone,
                'role' => $speaker->title,
                'attendance_status' => 'confirmed',
                'checked_in_at' => null,
                'notes' => null,
                'sort_order' => $speaker->sort_order ?? 1,
                'created_at' => $speaker->created_at,
                'updated_at' => $speaker->updated_at,
            ]);
        }

        $attendeeRows = DB::table('event_attendees')->get();
        foreach ($attendeeRows as $attendee) {
            DB::table('event_participants')->insert([
                'event_id' => $attendee->event_id,
                'category' => 'attendee',
                'name' => $attendee->name,
                'title' => null,
                'organization_name' => $attendee->organization_name,
                'topic' => null,
                'bio' => null,
                'email' => $attendee->email,
                'phone' => $attendee->phone,
                'role' => $attendee->role,
                'attendance_status' => $attendee->attendance_status ?? 'registered',
                'checked_in_at' => $attendee->checked_in_at,
                'notes' => null,
                'sort_order' => 1,
                'created_at' => $attendee->created_at,
                'updated_at' => $attendee->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
