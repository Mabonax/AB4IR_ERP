<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('completion_status')->default('not_submitted')->after('completed_at');
            $table->timestamp('submitted_for_verification_at')->nullable()->after('completion_status');
            $table->foreignId('submitted_by_user_id')->nullable()->after('submitted_for_verification_at')->constrained('users')->nullOnDelete();
            $table->text('manager_review_notes')->nullable()->after('submitted_by_user_id');
            $table->timestamp('reviewed_at')->nullable()->after('manager_review_notes');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('returned_for_amendments_at')->nullable()->after('reviewed_by_user_id');

            $table->index(['completion_status', 'submitted_for_verification_at'], 'event_tasks_completion_review_idx');
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropIndex('event_tasks_completion_review_idx');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('submitted_by_user_id');
            $table->dropColumn([
                'completion_status',
                'submitted_for_verification_at',
                'manager_review_notes',
                'reviewed_at',
                'returned_for_amendments_at',
            ]);
        });
    }
};
