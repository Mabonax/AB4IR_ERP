<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendance_overrides', function (Blueprint $table) {
            $table->unsignedBigInteger('opened_by_user_id')->nullable()->change();
            $table->text('reason')->nullable()->change();
            $table->foreignId('requested_by_user_id')->nullable()->after('staff_member_id')->constrained('users')->nullOnDelete();
            $table->text('request_reason')->nullable()->after('reason');
            $table->string('status')->default('approved')->after('request_reason');
            $table->timestamp('approved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance_overrides', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by_user_id');
            $table->dropColumn(['request_reason', 'status', 'approved_at']);
            $table->unsignedBigInteger('opened_by_user_id')->nullable(false)->change();
            $table->text('reason')->change();
        });
    }
};
