<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bds_incubatees', function (Blueprint $table): void {
            $table->string('intake_type')->default('pitch_adjudicated')->after('bds_application_id');
            $table->string('intake_source')->nullable()->after('intake_type');
            $table->text('intake_justification')->nullable()->after('intake_source');
            $table->timestamp('intake_approved_at')->nullable()->after('intake_justification');
            $table->foreignId('intake_approved_by')->nullable()->after('intake_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bds_incubatees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('intake_approved_by');
            $table->dropColumn([
                'intake_type',
                'intake_source',
                'intake_justification',
                'intake_approved_at',
            ]);
        });
    }
};
