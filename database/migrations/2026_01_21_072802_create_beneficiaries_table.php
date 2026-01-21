<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            // Personal info
            $table->string('name');
            $table->string('surname');
            $table->dateTime('dob');
            $table->integer('age');

            // Identification
            $table->string('id_number', 13)->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            // Demographics
            $table->enum('gender', ['male', 'female']);

            // Address
            $table->string('street_address')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->foreignId('location_id')->nullable()->constrained();
            $table->string('postal_code')->nullable();

            // Education
            $table->string('highest_qualification')->nullable();

            // Relations & audit
            $table->foreignId('next_of_kin_id')
                ->nullable()
                ->constrained('next_of_kin')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
