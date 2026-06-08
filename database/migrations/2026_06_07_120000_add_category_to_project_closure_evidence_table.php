<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_closure_evidence', function (Blueprint $table) {
            $table->string('category', 50)->default('evidence')->after('project_closure_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_closure_evidence', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
