<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_profiles', function (Blueprint $table) {
            $table->string('primary_logo_path')->nullable()->after('postal_code');
            $table->string('light_logo_path')->nullable()->after('primary_logo_path');
            $table->string('dark_logo_path')->nullable()->after('light_logo_path');
            $table->string('icon_logo_path')->nullable()->after('dark_logo_path');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('event_format')->nullable()->after('event_type');
            $table->string('venue_name')->nullable()->after('location');
            $table->text('venue_address')->nullable()->after('venue_name');
            $table->string('venue_contact_person')->nullable()->after('venue_address');
            $table->string('venue_contact_phone')->nullable()->after('venue_contact_person');
            $table->string('venue_contact_email')->nullable()->after('venue_contact_phone');
            $table->string('track_name')->nullable()->after('theme');
            $table->text('technical_requirements')->nullable()->after('objectives');
            $table->string('registration_link')->nullable()->after('technical_requirements');
            $table->string('zoom_join_url')->nullable()->after('registration_link');
            $table->string('zoom_host_url')->nullable()->after('zoom_join_url');
            $table->string('zoom_meeting_id')->nullable()->after('zoom_host_url');
            $table->string('zoom_passcode')->nullable()->after('zoom_meeting_id');
            $table->unsignedInteger('expected_attendees')->nullable()->after('zoom_passcode');
        });

        Schema::create('event_partner_stakeholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('stakeholder_id')->constrained('stakeholders')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'stakeholder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_partner_stakeholders');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_format',
                'venue_name',
                'venue_address',
                'venue_contact_person',
                'venue_contact_phone',
                'venue_contact_email',
                'track_name',
                'technical_requirements',
                'registration_link',
                'zoom_join_url',
                'zoom_host_url',
                'zoom_meeting_id',
                'zoom_passcode',
                'expected_attendees',
            ]);
        });

        Schema::table('organization_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'primary_logo_path',
                'light_logo_path',
                'dark_logo_path',
                'icon_logo_path',
            ]);
        });
    }
};
