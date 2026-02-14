<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('asset_batch_id')
                ->nullable()
                ->after('asset_category_id')
                ->constrained('asset_batches')
                ->nullOnDelete();

            $table->string('asset_code')->nullable()->after('type')->unique();
            $table->enum('serial_state', ['recorded', 'pending', 'no_serial'])
                ->default('recorded')
                ->after('asset_code');

            $table->string('serial_number')->nullable()->change();

            $table->index(['serial_state', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_batch_id');
            $table->dropUnique(['asset_code']);
            $table->dropColumn(['asset_code', 'serial_state']);
            $table->dropIndex(['serial_state', 'status']);
            $table->string('serial_number')->nullable(false)->change();
        });
    }
};

