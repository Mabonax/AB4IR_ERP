<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bds_incubatees', function (Blueprint $table) {
            $table->foreignId('bds_application_id')
                ->nullable()
                ->after('id')
                ->constrained('bds_applications')
                ->nullOnDelete();
            $table->unique('bds_application_id');
        });
    }

    public function down(): void
    {
        Schema::table('bds_incubatees', function (Blueprint $table) {
            $table->dropUnique(['bds_application_id']);
            $table->dropConstrainedForeignId('bds_application_id');
        });
    }
};
