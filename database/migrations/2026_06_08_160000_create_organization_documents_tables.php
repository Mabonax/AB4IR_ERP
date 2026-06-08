<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_profile_id')->constrained('organization_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type', 100);
            $table->text('description')->nullable();
            $table->enum('audience_scope', ['all_staff', 'department', 'selected_users'])->default('all_staff');
            $table->foreignId('department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
            $table->string('slot_key')->nullable();
            $table->boolean('replace_existing')->default(false);
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_type', 'slot_key']);
            $table->index('audience_scope');
        });

        Schema::create('organization_document_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_document_id')->constrained('organization_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organization_document_id', 'user_id'], 'org_document_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_document_user');
        Schema::dropIfExists('organization_documents');
    }
};
