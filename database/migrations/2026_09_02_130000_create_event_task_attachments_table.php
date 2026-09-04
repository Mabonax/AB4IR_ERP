<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_task_id')->constrained('event_tasks')->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('attachment_type')->default('supporting_document');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['event_task_id', 'attachment_type'], 'event_task_attachments_task_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_task_attachments');
    }
};
