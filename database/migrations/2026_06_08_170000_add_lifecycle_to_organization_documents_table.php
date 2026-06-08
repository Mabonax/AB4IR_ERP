<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_documents', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('replace_existing');
            $table->timestamp('effective_from')->nullable()->after('is_active');
            $table->timestamp('effective_until')->nullable()->after('effective_from');
            $table->index(['is_active', 'effective_from', 'effective_until'], 'org_documents_lifecycle_idx');
        });
    }

    public function down(): void
    {
        Schema::table('organization_documents', function (Blueprint $table) {
            $table->dropIndex('org_documents_lifecycle_idx');
            $table->dropColumn(['is_active', 'effective_from', 'effective_until']);
        });
    }
};
