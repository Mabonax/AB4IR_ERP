<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('id_number')->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 50)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('physical_address')->nullable();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('township_id')->nullable()->constrained('townships')->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('member_type');
            $table->string('status')->default('active');
            $table->boolean('disability_status')->default(false);
            $table->boolean('youth_indicator')->default(false);
            $table->boolean('veteran_indicator')->default(false);
            $table->unsignedInteger('household_size')->nullable();
            $table->unsignedInteger('dependants')->nullable();
            $table->timestamps();

            $table->index(['member_type', 'status']);
        });

        Schema::create('member_employment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('employment_status');
            $table->string('employer')->nullable();
            $table->string('occupation')->nullable();
            $table->string('industry')->nullable();
            $table->unsignedInteger('years_experience')->nullable();
            $table->string('monthly_income_band')->nullable();
            $table->timestamps();

            $table->unique('member_id');
        });

        Schema::create('member_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('qualification_type');
            $table->string('institution');
            $table->string('qualification_name');
            $table->string('field_of_study');
            $table->string('nqf_level')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('completed_flag')->default(false);
            $table->unsignedSmallInteger('completion_year')->nullable();
            $table->timestamps();
        });

        Schema::create('member_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('skill_name');
            $table->string('category')->nullable();
            $table->string('proficiency_level');
            $table->unsignedInteger('years_experience')->nullable();
            $table->timestamps();
        });

        Schema::create('member_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('employer');
            $table->string('position');
            $table->string('industry')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('current_employer_flag')->default(false);
            $table->text('responsibilities')->nullable();
            $table->timestamps();
        });

        Schema::create('member_opportunity_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('interest_type');
            $table->string('opportunity_category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('member_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('assignment_type');
            $table->nullableMorphs('assignable');
            $table->string('member_role')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_assignments');
        Schema::dropIfExists('member_opportunity_interests');
        Schema::dropIfExists('member_work_experiences');
        Schema::dropIfExists('member_skills');
        Schema::dropIfExists('member_qualifications');
        Schema::dropIfExists('member_employment_profiles');
        Schema::dropIfExists('members');
    }
};
