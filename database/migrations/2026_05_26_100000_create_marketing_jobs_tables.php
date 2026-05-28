<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('brief')->nullable();
            $table->enum('job_type', ['graphic_design', 'social_media', 'content_plan', 'letter_communication', 'email_signature', 'other']);
            $table->enum('status', ['open', 'in_progress', 'blocked', 'pending_approval', 'changes_requested', 'approved', 'cancelled'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('due_date')->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
            $table->text('delivery_notes')->nullable();
            $table->string('proof_disk')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_file_name')->nullable();
            $table->string('proof_mime_type')->nullable();
            $table->unsignedBigInteger('proof_file_size')->nullable();
            $table->string('proof_url', 2048)->nullable();
            $table->timestamp('submitted_for_approval_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_for_amendments_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assignment_notified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'job_type']);
            $table->index(['assigned_department_id', 'status']);
        });

        Schema::create('marketing_job_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_job_id')->constrained('marketing_jobs')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->enum('document_kind', ['supporting', 'concept', 'delivery', 'review_feedback', 'revised_submission', 'approval_reference'])->default('supporting');
            $table->text('notes')->nullable();
            $table->string('disk');
            $table->string('path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_job_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_job_id')->constrained('marketing_jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('marketing_job_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_job_id')->constrained('marketing_jobs')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('summary');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_job_histories');
        Schema::dropIfExists('marketing_job_comments');
        Schema::dropIfExists('marketing_job_documents');
        Schema::dropIfExists('marketing_jobs');
    }
};
