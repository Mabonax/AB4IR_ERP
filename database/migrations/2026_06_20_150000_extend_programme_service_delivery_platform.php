<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('code')->nullable()->after('title');
            $table->text('strategic_objective')->nullable()->after('description');
            $table->date('start_date')->nullable()->after('strategic_objective');
            $table->date('end_date')->nullable()->after('start_date');
            $table->enum('status', ['draft', 'planned', 'active', 'suspended', 'completed', 'cancelled'])
                ->default('draft')
                ->after('end_date');
            $table->decimal('budget', 15, 2)->nullable()->after('status');
            $table->string('funding_source')->nullable()->after('budget');
            $table->foreignId('responsible_committee_id')->nullable()->after('funding_source')
                ->constrained('committees')->nullOnDelete();
            $table->foreignId('programme_manager_id')->nullable()->after('responsible_committee_id')
                ->constrained('staff_members')->nullOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_code')->nullable()->after('name');
            $table->string('primary_location')->nullable()->after('project_code');
            $table->decimal('budget', 15, 2)->nullable()->after('funding_amount');
            $table->unsignedInteger('target_beneficiaries')->nullable()->after('budget');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('id');
            $table->string('beneficiary_number')->nullable()->after('member_id');
            $table->foreignId('program_id')->nullable()->after('project_id')
                ->constrained('programs')->nullOnDelete();
            $table->date('enrolment_date')->nullable()->after('program_id');
            $table->date('exit_date')->nullable()->after('enrolment_date');
            $table->enum('participation_status', ['registered', 'enrolled', 'active', 'completed', 'withdrawn', 'suspended'])
                ->nullable()->after('exit_date');
            $table->string('placement_status')->nullable()->after('participation_status');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->nullOnDelete();
        });

        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->string('assigned_team')->nullable();
            $table->timestamps();
        });

        Schema::create('beneficiary_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->string('employer');
            $table->enum('opportunity_type', ['internship', 'learnership', 'apprenticeship', 'employment', 'volunteer_placement']);
            $table->date('placement_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('status')->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('programme_partnerships', function (Blueprint $table) {
            $table->id();
            $table->string('organisation');
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->enum('partnership_type', ['government', 'private_sector', 'ngo', 'academic_institution', 'donor']);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('programme_partnership_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_partnership_id')->constrained('programme_partnerships')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['programme_partnership_id', 'program_id'], 'programme_partnership_program_unique');
        });

        Schema::create('programme_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('target')->default(0);
            $table->unsignedInteger('actual')->default(0);
            $table->string('reporting_period');
            $table->timestamps();
        });

        Schema::create('programme_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('category');
            $table->string('name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('project_activity_id')->nullable()->constrained('project_activities')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->enum('attendance_type', ['workshop', 'training', 'event', 'meeting'])->default('training');
            $table->date('attendance_date');
            $table->string('attendance_status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_attendances');
        Schema::dropIfExists('programme_documents');
        Schema::dropIfExists('programme_outcomes');
        Schema::dropIfExists('programme_partnership_program');
        Schema::dropIfExists('programme_partnerships');
        Schema::dropIfExists('beneficiary_placements');
        Schema::dropIfExists('project_activities');

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropForeign(['program_id']);
            $table->dropColumn([
                'member_id',
                'beneficiary_number',
                'program_id',
                'enrolment_date',
                'exit_date',
                'participation_status',
                'placement_status',
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'project_code',
                'primary_location',
                'budget',
                'target_beneficiaries',
            ]);
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['responsible_committee_id']);
            $table->dropForeign(['programme_manager_id']);
            $table->dropColumn([
                'code',
                'strategic_objective',
                'start_date',
                'end_date',
                'status',
                'budget',
                'funding_source',
                'responsible_committee_id',
                'programme_manager_id',
            ]);
        });
    }
};
