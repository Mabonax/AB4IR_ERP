<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tagline')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->longText('about')->nullable();
            $table->text('core_values')->nullable();
            $table->text('service_offering')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('event_type')->nullable();
            $table->string('annual_series_key')->nullable();
            $table->unsignedInteger('event_year')->nullable();
            $table->boolean('is_annual')->default(true);
            $table->string('theme')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('planned');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->foreignId('owner_staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('topic')->nullable();
            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('role')->nullable();
            $table->string('attendance_status')->default('registered');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('event_speakers');
        Schema::dropIfExists('events');
        Schema::dropIfExists('organization_profiles');
    }
};
