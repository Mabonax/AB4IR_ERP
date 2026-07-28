<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->unique();
            $table->string('organisation_type');
            $table->string('npo_number')->nullable();
            $table->string('pbo_number')->nullable();
            $table->string('tax_reference_number')->nullable();
            $table->string('constitution_version')->nullable();
            $table->date('registered_at')->nullable();
            $table->date('npo_registered_at')->nullable();
            $table->date('pbo_registered_at')->nullable();
            $table->json('contact_details')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisations');
    }
};
