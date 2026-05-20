<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('claimant_staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('checked_by_staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->date('claim_month');
            $table->string('claimant_name');
            $table->text('claimant_address')->nullable();
            $table->string('vehicle_make_model');
            $table->string('vehicle_type');
            $table->unsignedInteger('vehicle_year');
            $table->string('engine_volume');
            $table->decimal('tariff_per_km', 10, 2)->default(4.84);
            $table->decimal('home_distance_km', 10, 2)->default(0);
            $table->enum('status', ['submitted', 'received', 'paid', 'rejected'])->default('submitted');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('finance_received_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_paid_at')->nullable();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('finance_comment')->nullable();
            $table->decimal('total_actual_distance_km', 10, 2)->default(0);
            $table->decimal('total_claimable_distance_km', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('travel_claim_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_claim_id')->constrained('travel_claims')->cascadeOnDelete();
            $table->date('travel_date');
            $table->string('route_from');
            $table->string('route_to');
            $table->string('start_time');
            $table->string('end_time');
            $table->text('nature_of_duty');
            $table->decimal('actual_distance_km', 10, 2)->default(0);
            $table->decimal('claimable_distance_km', 10, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_claim_trips');
        Schema::dropIfExists('travel_claims');
    }
};
