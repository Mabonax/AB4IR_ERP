<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->string('evidence_disk')->nullable()->after('comment');
            $table->string('evidence_path')->nullable()->after('evidence_disk');
            $table->string('evidence_file_name')->nullable()->after('evidence_path');
            $table->string('evidence_mime_type')->nullable()->after('evidence_file_name');
            $table->unsignedBigInteger('evidence_file_size')->nullable()->after('evidence_mime_type');
            $table->string('evidence_url')->nullable()->after('evidence_file_size');
            $table->timestamp('completed_at')->nullable()->after('evidence_url');
        });
    }

    public function down(): void
    {
        Schema::table('event_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'evidence_disk',
                'evidence_path',
                'evidence_file_name',
                'evidence_mime_type',
                'evidence_file_size',
                'evidence_url',
                'completed_at',
            ]);
        });
    }
};
