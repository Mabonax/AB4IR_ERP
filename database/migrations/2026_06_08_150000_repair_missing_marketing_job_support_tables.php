<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_job_documents')) {
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
        }

        if (! Schema::hasTable('marketing_job_comments')) {
            Schema::create('marketing_job_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_job_id')->constrained('marketing_jobs')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marketing_job_histories')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_job_histories');
        Schema::dropIfExists('marketing_job_comments');
        Schema::dropIfExists('marketing_job_documents');
    }
};
