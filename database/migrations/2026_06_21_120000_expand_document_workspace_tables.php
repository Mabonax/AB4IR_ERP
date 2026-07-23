<?php

use App\Domains\Documents\Models\DocumentFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_files', function (Blueprint $table) {
            $table->string('status', 50)->default('draft')->after('version');
            $table->foreignId('checked_out_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('checked_out_at')->nullable()->after('checked_out_by');

            $table->index('status');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('document_files')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('disk', 80)->default('local');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_number'], 'document_versions_unique_idx');
            $table->index(['document_id', 'created_at'], 'document_versions_document_created_idx');
        });

        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('document_files')->cascadeOnDelete();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->string('relationship_type', 50);
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['document_id', 'linkable_type', 'linkable_id', 'relationship_type'],
                'document_links_unique_idx'
            );
            $table->index(['linkable_type', 'linkable_id'], 'document_links_linkable_idx');
        });

        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('document_files')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50);
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status'], 'document_approvals_document_status_idx');
        });

        Schema::create('document_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('document_files')->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'created_at'], 'document_activity_logs_document_created_idx');
            $table->index(['action', 'created_at'], 'document_activity_logs_action_created_idx');
        });

        Schema::create('document_repository_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('owner_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('document_repository_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('document_repository_templates')->cascadeOnDelete();
            $table->foreignId('parent_item_id')->nullable()->constrained('document_repository_template_items')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'sort_order'], 'document_template_items_template_sort_idx');
        });

        $now = now();

        DocumentFile::query()
            ->select(['id', 'disk', 'file_path', 'original_name', 'mime_type', 'size_bytes', 'uploaded_by', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($files) use ($now) {
                $rows = $files->map(fn (DocumentFile $file) => [
                    'document_id' => $file->id,
                    'version_number' => max((int) $file->version, 1),
                    'disk' => $file->disk,
                    'file_path' => $file->file_path,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size_bytes' => $file->size_bytes,
                    'uploaded_by' => $file->uploaded_by,
                    'notes' => 'Initial imported version.',
                    'created_at' => $file->created_at ?? $now,
                    'updated_at' => $file->updated_at ?? $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('document_versions')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_repository_template_items');
        Schema::dropIfExists('document_repository_templates');
        Schema::dropIfExists('document_activity_logs');
        Schema::dropIfExists('document_approvals');
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('document_versions');

        Schema::table('document_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checked_out_by');
            $table->dropColumn(['checked_out_at', 'status']);
        });
    }
};
