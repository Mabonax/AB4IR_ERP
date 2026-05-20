<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->timestamp('assignment_notified_at')->nullable()->after('completed_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('assignment_notified_at');
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->timestamp('assignment_notified_at')->nullable()->after('closed_by_user_id');
            $table->timestamp('resolved_notified_at')->nullable()->after('assignment_notified_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('resolved_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropColumn(['assignment_notified_at', 'overdue_notified_at']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['assignment_notified_at', 'resolved_notified_at', 'overdue_notified_at']);
        });
    }
};
