<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilitators', function (Blueprint $table) {
            $table->date('dob')->nullable()->change();
            $table->string('id_number', 20)->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->string('cell', 20)->nullable()->change();
            $table->string('specialization')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('facilitators', function (Blueprint $table) {
            $table->date('dob')->nullable(false)->change();
            $table->string('id_number', 20)->nullable(false)->change();
            $table->string('address')->nullable(false)->change();
            $table->string('cell', 20)->nullable(false)->change();
            $table->string('specialization')->nullable(false)->change();
        });
    }
};
