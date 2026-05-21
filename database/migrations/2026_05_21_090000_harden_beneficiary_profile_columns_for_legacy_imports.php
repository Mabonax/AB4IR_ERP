<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->date('dob')->nullable()->change();
            $table->integer('age')->nullable()->change();
            $table->string('id_number', 13)->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->enum('gender', ['male', 'female'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dateTime('dob')->nullable(false)->change();
            $table->integer('age')->nullable(false)->change();
            $table->string('id_number', 13)->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->enum('gender', ['male', 'female'])->nullable(false)->change();
        });
    }
};
