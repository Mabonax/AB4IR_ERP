<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('asset_batches', 'model_name')) {
            Schema::table('asset_batches', function (Blueprint $table) {
                $table->string('model_name')->nullable()->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('asset_batches', 'model_name')) {
            Schema::table('asset_batches', function (Blueprint $table) {
                $table->dropColumn('model_name');
            });
        }
    }
};
