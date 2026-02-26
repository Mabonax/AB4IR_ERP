<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd_adjudication_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('bd_adjudication_assessments')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('bd_adjudication_sections')->restrictOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_adjudication_scores');
    }
};
