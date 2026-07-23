<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->string('status', 50)->default('enrolled')->after('attendance_status');
            $table->text('status_reason')->nullable()->after('status');

            $table->timestamp('graduated_at')->nullable()->after('status_reason');
            $table->foreignId('graduated_by')->nullable()->after('graduated_at')->constrained('users')->nullOnDelete();

            $table->timestamp('exited_at')->nullable()->after('graduated_by');
            $table->foreignId('exited_by')->nullable()->after('exited_at')->constrained('users')->nullOnDelete();
            $table->text('exit_reason')->nullable()->after('exited_by');

            $table->timestamp('suspended_at')->nullable()->after('exit_reason');
            $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();

            $table->timestamp('reactivated_at')->nullable()->after('suspended_by');
            $table->foreignId('reactivated_by')->nullable()->after('reactivated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reactivated_by');
            $table->dropColumn('reactivated_at');
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn('suspended_at');
            $table->dropColumn('exit_reason');
            $table->dropConstrainedForeignId('exited_by');
            $table->dropColumn('exited_at');
            $table->dropConstrainedForeignId('graduated_by');
            $table->dropColumn('graduated_at');
            $table->dropColumn(['status', 'status_reason']);
        });
    }
};
