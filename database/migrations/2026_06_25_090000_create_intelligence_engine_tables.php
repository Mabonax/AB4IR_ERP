<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 30)->index();
            $table->text('purpose')->nullable();
            $table->longText('system_instructions')->nullable();
            $table->string('default_provider', 100);
            $table->string('default_model', 150);
            $table->decimal('temperature', 3, 2)->default(0.20);
            $table->unsignedInteger('max_tokens')->default(1024);
            $table->json('allowed_tools')->nullable();
            $table->json('allowed_knowledge_sources')->nullable();
            $table->boolean('memory_enabled')->default(true);
            $table->unsignedInteger('conversation_limit')->default(30);
            $table->string('visibility', 30)->default('organization');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->string('category', 100)->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 30)->index();
            $table->longText('system_prompt')->nullable();
            $table->longText('developer_prompt')->nullable();
            $table->longText('user_prompt_template')->nullable();
            $table->json('variables_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category', 100)->index();
            $table->string('handler_class');
            $table->json('input_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->string('status', 30)->index();
            $table->boolean('requires_approval')->default(false);
            $table->string('permission_key')->nullable();
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('model_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 100);
            $table->string('model', 150);
            $table->string('capability', 50)->index();
            $table->unsignedInteger('priority')->default(1);
            $table->unsignedInteger('max_context_tokens')->default(8000);
            $table->string('cost_tier', 50)->default('standard');
            $table->boolean('enabled')->default(true);
            $table->string('fallback_provider', 100)->nullable();
            $table->string('fallback_model', 150)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('intelligence_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('intelligence_conversations')->cascadeOnDelete();
            $table->string('role', 30)->index();
            $table->longText('content');
            $table->string('provider', 100)->nullable();
            $table->string('model', 150)->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('memory_records', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 100)->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->string('memory_type', 50)->index();
            $table->longText('content');
            $table->decimal('confidence_score', 4, 2)->default(0.60);
            $table->foreignId('source_conversation_id')->nullable()->constrained('intelligence_conversations')->nullOnDelete();
            $table->foreignId('source_message_id')->nullable()->constrained('intelligence_messages')->nullOnDelete();
            $table->string('visibility', 30)->default('organization')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tool_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_tool_id')->constrained('ai_tools')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('intelligence_conversations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->index();
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamp('executed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_execution_logs');
        Schema::dropIfExists('memory_records');
        Schema::dropIfExists('intelligence_messages');
        Schema::dropIfExists('intelligence_conversations');
        Schema::dropIfExists('model_routing_rules');
        Schema::dropIfExists('ai_tools');
        Schema::dropIfExists('prompt_templates');
        Schema::dropIfExists('agents');
    }
};
