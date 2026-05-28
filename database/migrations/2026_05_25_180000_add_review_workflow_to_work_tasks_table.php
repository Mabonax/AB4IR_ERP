<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->string('proof_disk')->nullable()->after('completion_notes');
            $table->string('proof_path')->nullable()->after('proof_disk');
            $table->string('proof_file_name')->nullable()->after('proof_path');
            $table->string('proof_mime_type')->nullable()->after('proof_file_name');
            $table->unsignedBigInteger('proof_file_size')->nullable()->after('proof_mime_type');
            $table->string('proof_url')->nullable()->after('proof_file_size');
            $table->timestamp('submitted_for_review_at')->nullable()->after('proof_url');
            $table->foreignId('submitted_by_user_id')->nullable()->after('submitted_for_review_at')->constrained('users')->nullOnDelete();
            $table->text('manager_review_notes')->nullable()->after('submitted_by_user_id');
            $table->timestamp('reviewed_at')->nullable()->after('manager_review_notes');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('returned_for_amendments_at')->nullable()->after('reviewed_by_user_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE work_tasks MODIFY status ENUM('open', 'in_progress', 'blocked', 'pending_review', 'changes_requested', 'completed', 'cancelled') NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE work_tasks SET status = 'in_progress' WHERE status IN ('pending_review', 'changes_requested')");
            DB::statement("ALTER TABLE work_tasks MODIFY status ENUM('open', 'in_progress', 'blocked', 'completed', 'cancelled') NOT NULL DEFAULT 'open'");
        }

        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by_user_id');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn([
                'proof_disk',
                'proof_path',
                'proof_file_name',
                'proof_mime_type',
                'proof_file_size',
                'proof_url',
                'submitted_for_review_at',
                'manager_review_notes',
                'reviewed_at',
                'returned_for_amendments_at',
            ]);
        });
    }
};
