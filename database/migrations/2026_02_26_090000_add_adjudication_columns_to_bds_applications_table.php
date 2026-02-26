<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bds_applications', function (Blueprint $table) {
            $table->enum('adjudication_result', ['incubated', 'rejected'])->nullable()->after('pitch_notes');
            $table->timestamp('adjudicated_at')->nullable()->after('adjudication_result');
            $table->index('adjudication_result');
        });
    }

    public function down(): void
    {
        Schema::table('bds_applications', function (Blueprint $table) {
            $table->dropIndex(['adjudication_result']);
            $table->dropColumn(['adjudication_result', 'adjudicated_at']);
        });
    }
};
