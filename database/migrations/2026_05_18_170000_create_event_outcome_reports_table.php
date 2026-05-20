<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_outcome_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->longText('summary')->nullable();
            $table->longText('highlights')->nullable();
            $table->longText('opportunities_created')->nullable();
            $table->longText('partnerships_formed')->nullable();
            $table->longText('training_opportunities')->nullable();
            $table->longText('media_coverage')->nullable();
            $table->longText('statistics_summary')->nullable();
            $table->text('thank_you_status')->nullable();
            $table->longText('follow_up_actions')->nullable();
            $table->string('report_status')->default('draft');
            $table->foreignId('reported_by_staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_outcome_reports');
    }
};
