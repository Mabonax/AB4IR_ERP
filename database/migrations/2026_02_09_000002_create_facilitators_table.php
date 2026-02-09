<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilitators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->date('dob');
            $table->string('id_number', 20)->unique();
            $table->string('address');
            $table->string('email')->unique();
            $table->string('cell', 20);
            $table->string('specialization');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilitators');
    }
};
