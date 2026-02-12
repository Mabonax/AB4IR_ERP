<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->foreignId('manager_id')
                ->nullable()
                ->after('department_id')
                ->constrained('staff_members')
                ->nullOnDelete();
            $table->date('start_date')->nullable()->after('employee_number');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['manager_id', 'start_date']);
        });
    }
};
