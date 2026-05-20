<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_claims', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('status');
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->after('received_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approval_decided_at')->nullable()->after('approved_by_user_id');
            $table->text('approval_comment')->nullable()->after('finance_comment');

            $table->string('vehicle_make_model')->nullable()->change();
            $table->string('vehicle_type')->nullable()->change();
            $table->unsignedInteger('vehicle_year')->nullable()->change();
            $table->string('engine_volume')->nullable()->change();
        });

        Schema::table('travel_claim_trips', function (Blueprint $table) {
            $table->string('start_time')->nullable()->change();
            $table->string('end_time')->nullable()->change();
            $table->text('nature_of_duty')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('travel_claim_trips', function (Blueprint $table) {
            $table->string('start_time')->nullable(false)->change();
            $table->string('end_time')->nullable(false)->change();
            $table->text('nature_of_duty')->nullable(false)->change();
        });

        Schema::table('travel_claims', function (Blueprint $table) {
            $table->string('vehicle_make_model')->nullable(false)->change();
            $table->string('vehicle_type')->nullable(false)->change();
            $table->unsignedInteger('vehicle_year')->nullable(false)->change();
            $table->string('engine_volume')->nullable(false)->change();

            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn([
                'approval_status',
                'approved_by_user_id',
                'approval_decided_at',
                'approval_comment',
            ]);
        });
    }
};
