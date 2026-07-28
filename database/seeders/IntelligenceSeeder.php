<?php

namespace Database\Seeders;

use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Models\AiTool;
use App\Domains\Intelligence\Models\ModelRoutingRule;
use App\Domains\Intelligence\Models\PromptTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class IntelligenceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->first();

        $toolDefinitions = [
            [
                'name' => 'Current Datetime',
                'slug' => 'current_datetime',
                'description' => 'Returns the platform datetime.',
                'category' => 'system',
                'handler_class' => \App\Domains\Intelligence\Handlers\CurrentDatetimeToolHandler::class,
            ],
            [
                'name' => 'Calculator',
                'slug' => 'calculator',
                'description' => 'Performs safe arithmetic.',
                'category' => 'utility',
                'handler_class' => \App\Domains\Intelligence\Handlers\CalculatorToolHandler::class,
            ],
            [
                'name' => 'Platform Status',
                'slug' => 'platform_status',
                'description' => 'Returns a small internal platform health snapshot.',
                'category' => 'system',
                'handler_class' => \App\Domains\Intelligence\Handlers\PlatformStatusToolHandler::class,
            ],
            [
                'name' => 'Conversation Summary',
                'slug' => 'conversation_summary',
                'description' => 'Summarizes the latest messages in a conversation.',
                'category' => 'conversation',
                'handler_class' => \App\Domains\Intelligence\Handlers\ConversationSummaryToolHandler::class,
            ],
            [
                'name' => 'Document Lookup Stub',
                'slug' => 'document_lookup_stub',
                'description' => 'Placeholder for future document search.',
                'category' => 'knowledge',
                'handler_class' => \App\Domains\Intelligence\Handlers\DocumentLookupStubToolHandler::class,
            ],
            [
                'name' => 'ERP Lookup Stub',
                'slug' => 'erp_lookup_stub',
                'description' => 'Placeholder for future ERP record lookups.',
                'category' => 'knowledge',
                'handler_class' => \App\Domains\Intelligence\Handlers\ErpLookupStubToolHandler::class,
            ],
        ];

        foreach ($toolDefinitions as $definition) {
            AiTool::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                array_merge($definition, [
                    'input_schema' => ['type' => 'object'],
                    'output_schema' => ['type' => 'object'],
                    'status' => 'active',
                    'requires_approval' => false,
                    'permission_key' => 'domain.intelligence.manage',
                    'timeout_seconds' => 10,
                    'metadata' => [],
                ])
            );
        }

        PromptTemplate::query()->updateOrCreate(
            ['slug' => 'poa-enterprise-default', 'version' => 1],
            [
                'name' => 'POA Enterprise Default',
                'description' => 'Default orchestration prompt for the Phase 3 intelligence engine.',
                'category' => 'operations',
                'status' => 'active',
                'system_prompt' => 'You are the POA Enterprise Intelligence Engine. Work safely, keep provider-neutral, and stay inside the ERP context.',
                'developer_prompt' => 'Prefer safe, auditable orchestration. Do not call destructive tools.',
                'user_prompt_template' => '{{message}}',
                'variables_schema' => ['properties' => ['message' => ['type' => 'string']]],
                'output_schema' => ['type' => 'object'],
                'owner_user_id' => $owner?->id,
                'is_default' => true,
                'metadata' => [],
            ]
        );

        Agent::query()->updateOrCreate(
            ['slug' => config('intelligence.default_agent_slug')],
            [
                'name' => 'POA Enterprise Orchestrator',
                'description' => 'Default provider-neutral enterprise intelligence orchestrator.',
                'status' => 'active',
                'purpose' => 'Route prompts, retrieve memory, execute safe tools, and produce auditable stub responses.',
                'system_instructions' => 'Operate inside the Programme of Action ERP. Stay audit-safe and tool-safe.',
                'default_provider' => 'stub',
                'default_model' => 'stub-chat-v1',
                'temperature' => 0.2,
                'max_tokens' => 1024,
                'allowed_tools' => array_column($toolDefinitions, 'slug'),
                'allowed_knowledge_sources' => ['organization', 'projects', 'programs', 'documents'],
                'memory_enabled' => true,
                'conversation_limit' => 30,
                'visibility' => 'organization',
                'owner_user_id' => $owner?->id,
                'metadata' => ['phase' => 'phase_3'],
            ]
        );

        foreach (['chat', 'tool_use', 'reasoning', 'summarization'] as $index => $capability) {
            ModelRoutingRule::query()->updateOrCreate(
                ['capability' => $capability, 'provider' => 'stub', 'model' => 'stub-chat-v1'],
                [
                    'priority' => $index + 1,
                    'max_context_tokens' => 8000,
                    'cost_tier' => 'stub',
                    'enabled' => true,
                    'fallback_provider' => 'stub',
                    'fallback_model' => 'stub-chat-v1',
                    'metadata' => [],
                ]
            );
        }
    }
}
