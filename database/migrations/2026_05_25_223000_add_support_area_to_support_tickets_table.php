<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->enum('support_area', ['hardware', 'software'])
                ->default('software')
                ->after('priority');

            $table->index(['support_area', 'status']);
        });

        DB::table('support_tickets')
            ->whereNull('support_area')
            ->update(['support_area' => 'software']);
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['support_area', 'status']);
            $table->dropColumn('support_area');
        });
    }
};
