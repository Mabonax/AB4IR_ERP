<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_series', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('series_key')->unique();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->string('default_title_pattern')->nullable();
            $table->string('default_event_type')->nullable();
            $table->string('default_format')->nullable();
            $table->string('default_theme')->nullable();
            $table->string('status', 40)->default('active');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'name'], 'event_series_status_name_idx');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('event_series_id')
                ->nullable()
                ->after('event_format')
                ->constrained('event_series')
                ->nullOnDelete();

            $table->unique(['event_series_id', 'event_year'], 'events_series_year_unique');
            $table->index(['event_series_id', 'event_year'], 'events_series_year_idx');
        });

        Schema::create('event_series_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_series_id')->constrained('event_series')->cascadeOnDelete();
            $table->foreignId('document_file_id')->constrained('document_files')->cascadeOnDelete();
            $table->string('asset_type', 80);
            $table->string('label')->nullable();
            $table->unsignedInteger('year')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(1);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_series_id', 'document_file_id', 'asset_type'], 'event_series_asset_unique');
            $table->index(['event_series_id', 'asset_type', 'year'], 'event_series_asset_lookup_idx');
            $table->index(['event_series_id', 'is_featured', 'display_order'], 'event_series_asset_featured_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_series_assets');

        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_series_year_unique');
            $table->dropIndex('events_series_year_idx');
            $table->dropConstrainedForeignId('event_series_id');
        });

        Schema::dropIfExists('event_series');
    }
};
