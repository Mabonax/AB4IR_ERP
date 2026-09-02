<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketing_requests') && Schema::hasTable('work_tasks') && ! Schema::hasColumn('marketing_requests', 'work_task_id')) {
            Schema::table('marketing_requests', function (Blueprint $table) {
                $table->foreignId('work_task_id')
                    ->nullable()
                    ->after('source_marketing_job_id')
                    ->constrained('work_tasks')
                    ->nullOnDelete();

                $table->index(['work_task_id', 'status'], 'mkt_requests_work_task_status_idx');
            });
        }

        if (Schema::hasTable('marketing_deliverables') && Schema::hasTable('work_tasks') && ! Schema::hasColumn('marketing_deliverables', 'work_task_id')) {
            Schema::table('marketing_deliverables', function (Blueprint $table) {
                $table->foreignId('work_task_id')
                    ->nullable()
                    ->after('source_marketing_job_id')
                    ->constrained('work_tasks')
                    ->nullOnDelete();

                $table->index(['work_task_id', 'status'], 'mkt_deliverables_work_task_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketing_deliverables') && Schema::hasColumn('marketing_deliverables', 'work_task_id')) {
            Schema::table('marketing_deliverables', function (Blueprint $table) {
                $table->dropForeign(['work_task_id']);
                $table->dropIndex('mkt_deliverables_work_task_status_idx');
                $table->dropColumn('work_task_id');
            });
        }

        if (Schema::hasTable('marketing_requests') && Schema::hasColumn('marketing_requests', 'work_task_id')) {
            Schema::table('marketing_requests', function (Blueprint $table) {
                $table->dropForeign(['work_task_id']);
                $table->dropIndex('mkt_requests_work_task_status_idx');
                $table->dropColumn('work_task_id');
            });
        }
    }
};
