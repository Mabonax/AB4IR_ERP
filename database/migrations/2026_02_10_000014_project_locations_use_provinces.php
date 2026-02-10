<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_locations', function (Blueprint $table) {
            $table->foreignId('province_id')->constrained('provinces')->restrictOnDelete();
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('project_locations', function (Blueprint $table) {
            $table->string('location');
            $table->dropForeign(['province_id']);
            $table->dropColumn('province_id');
        });
    }
};
