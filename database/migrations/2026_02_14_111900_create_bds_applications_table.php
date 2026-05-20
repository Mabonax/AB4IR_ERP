<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bds_applications', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('id_number');
            $table->string('gender', 50);
            $table->string('mobile_number');
            $table->string('email');
            $table->string('company_name');
            $table->string('company_registration_number');
            $table->string('position_in_company')->nullable();
            $table->string('majority_shareholding')->nullable();
            $table->unsignedInteger('current_number_of_employees')->default(0);
            $table->text('physical_address')->nullable();
            $table->string('website_address')->nullable();
            $table->unsignedInteger('years_in_operation')->default(0);
            $table->foreignId('province_id')->constrained('provinces')->restrictOnDelete();
            $table->boolean('has_business_plan')->default(false);
            $table->text('relevant_skill_set');
            $table->text('technology_product_service');
            $table->text('technology_stage_of_development');
            $table->date('application_date')->nullable();
            $table->enum('assessment_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->foreignId('assessed_by_staff_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('pitch_scheduled_at')->nullable();
            $table->text('pitch_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('id_number');
            $table->unique('company_registration_number');
            $table->index(['assessment_status', 'pitch_scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bds_applications');
    }
};
