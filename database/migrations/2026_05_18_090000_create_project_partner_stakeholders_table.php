<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_partner_stakeholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('stakeholder_id')->constrained('stakeholders')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'stakeholder_id'], 'project_partner_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_partner_stakeholders');
    }
};
