<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('type');
            $table->string('model_name');
            $table->unsignedInteger('quantity');
            $table->enum('serial_state', ['recorded', 'pending', 'no_serial'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_batches');
    }
};
