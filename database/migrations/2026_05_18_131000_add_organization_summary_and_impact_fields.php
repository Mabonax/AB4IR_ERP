<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_profiles', function (Blueprint $table) {
            $table->longText('objectives')->nullable()->after('vision');
            $table->text('focus_areas')->nullable()->after('objectives');
            $table->unsignedBigInteger('impact_total')->nullable()->after('focus_areas');
            $table->unsignedBigInteger('impact_digital')->nullable()->after('impact_total');
            $table->unsignedBigInteger('impact_physical')->nullable()->after('impact_digital');
            $table->unsignedBigInteger('trainings_conducted')->nullable()->after('impact_physical');
            $table->unsignedBigInteger('impact_website')->nullable()->after('trainings_conducted');
            $table->unsignedBigInteger('impact_walkins')->nullable()->after('impact_website');
            $table->unsignedBigInteger('impact_facebook')->nullable()->after('impact_walkins');
            $table->unsignedBigInteger('impact_x')->nullable()->after('impact_facebook');
            $table->unsignedBigInteger('impact_linkedin')->nullable()->after('impact_x');
            $table->unsignedBigInteger('impact_livestreaming')->nullable()->after('impact_linkedin');
            $table->unsignedBigInteger('impact_instagram')->nullable()->after('impact_livestreaming');
            $table->unsignedBigInteger('impact_youtube')->nullable()->after('impact_instagram');
        });
    }

    public function down(): void
    {
        Schema::table('organization_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'objectives',
                'focus_areas',
                'impact_total',
                'impact_digital',
                'impact_physical',
                'trainings_conducted',
                'impact_website',
                'impact_walkins',
                'impact_facebook',
                'impact_x',
                'impact_linkedin',
                'impact_livestreaming',
                'impact_instagram',
                'impact_youtube',
            ]);
        });
    }
};
