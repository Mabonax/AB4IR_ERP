<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_locations', function (Blueprint $table) {
            $table->string('training_venue_address', 255)->nullable()->after('province_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_locations', function (Blueprint $table) {
            $table->dropColumn('training_venue_address');
        });
    }
};
