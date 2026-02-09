<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stakeholder_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stakeholder_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('contact_number');
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stakeholder_contacts');
    }
};
