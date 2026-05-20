<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('asset_id')
                ->nullable()
                ->after('program_id')
                ->constrained('assets')
                ->nullOnDelete();

            $table->index(['asset_id', 'status']);
        });

        Schema::create('asset_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('support_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('issue_summary');
            $table->text('maintenance_notes')->nullable();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
        });

        Schema::create('asset_decommission_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->unique()->constrained('assets')->cascadeOnDelete();
            $table->foreignId('decommissioned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamp('decommissioned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_decommission_records');
        Schema::dropIfExists('asset_maintenance_records');

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['asset_id', 'status']);
            $table->dropConstrainedForeignId('asset_id');
        });
    }
};
