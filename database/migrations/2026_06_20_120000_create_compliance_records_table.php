<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('organisations')->cascadeOnDelete();
            $table->string('title');
            $table->string('compliance_area');
            $table->string('reference_code')->nullable();
            $table->string('filing_frequency')->nullable();
            $table->date('due_date')->nullable();
            $table->date('submitted_at')->nullable();
            $table->string('status')->default('planned');
            $table->string('owner_name')->nullable();
            $table->json('meta')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['organisation_id', 'compliance_area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_records');
    }
};
