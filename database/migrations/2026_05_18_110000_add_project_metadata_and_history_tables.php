<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('contract_reference')->nullable()->after('project_manager_id');
            $table->decimal('funding_amount', 14, 2)->nullable()->after('contract_reference');
            $table->string('reporting_cadence')->nullable()->after('funding_amount');
            $table->text('reporting_obligations')->nullable()->after('reporting_cadence');
        });

        Schema::create('project_closure_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_closure_id')->nullable()->constrained('project_closures')->nullOnDelete();
            $table->string('title');
            $table->string('file_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('summary');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_history');
        Schema::dropIfExists('project_closure_evidence');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'contract_reference',
                'funding_amount',
                'reporting_cadence',
                'reporting_obligations',
            ]);
        });
    }
};
