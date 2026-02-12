<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 6, 2);
            $table->text('reason')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'manager_approved',
                'manager_rejected',
                'hr_approved',
                'hr_rejected',
                'cancelled',
            ])->default('submitted');
            $table->text('manager_comment')->nullable();
            $table->text('hr_comment')->nullable();
            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
