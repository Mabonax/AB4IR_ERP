<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('document_file_id')->constrained('document_files')->cascadeOnDelete();
            $table->string('document_kind', 80)->default('supporting_document');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['leave_request_id', 'document_kind'], 'leave_request_documents_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_documents');
    }
};
