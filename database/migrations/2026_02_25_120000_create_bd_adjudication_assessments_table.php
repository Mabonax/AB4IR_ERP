<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd_adjudication_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smme_id')->constrained('bds_applications')->restrictOnDelete();
            $table->foreignId('judge_id')->constrained('users')->restrictOnDelete();
            $table->string('platform_name');
            $table->date('adjudication_date');
            $table->enum('development_stage', ['mvp', 'prototype', 'complete_product']);
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->unsignedInteger('total_score')->default(0);
            $table->text('additional_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['judge_id', 'status']);
            $table->index(['smme_id', 'adjudication_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_adjudication_assessments');
    }
};
