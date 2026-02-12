<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilitators', function (Blueprint $table) {
            $table->foreignId('province_id')
                ->nullable()
                ->after('specialization')
                ->constrained('provinces')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('facilitators', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropColumn('province_id');
        });
    }
};
