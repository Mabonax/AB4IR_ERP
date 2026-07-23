<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('registration_opened_at')->nullable()->after('status_reason');
            $table->timestamp('registration_closed_at')->nullable()->after('registration_opened_at');
            $table->timestamp('started_at')->nullable()->after('registration_closed_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->timestamp('postponed_at')->nullable()->after('cancelled_at');
            $table->timestamp('archived_at')->nullable()->after('postponed_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'status_reason',
                'registration_opened_at',
                'registration_closed_at',
                'started_at',
                'completed_at',
                'cancelled_at',
                'postponed_at',
                'archived_at',
            ]);
        });
    }
};
