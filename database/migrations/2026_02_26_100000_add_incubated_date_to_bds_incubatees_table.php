<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bds_incubatees', function (Blueprint $table) {
            $table->date('incubated_date')->nullable()->after('status');
            $table->index('incubated_date');
        });
    }

    public function down(): void
    {
        Schema::table('bds_incubatees', function (Blueprint $table) {
            $table->dropIndex(['incubated_date']);
            $table->dropColumn('incubated_date');
        });
    }
};
