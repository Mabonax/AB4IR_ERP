<?php

return [
    'default_agent_slug' => env('INTELLIGENCE_DEFAULT_AGENT', 'poa-enterprise-orchestrator'),
    'memory' => [
        'enabled' => env('INTELLIGENCE_MEMORY_ENABLED', true),
        'default_limit' => 5,
        'minimum_confidence' => 0.55,
        'allowed_types' => [
            'preference',
            'fact',
            'instruction',
            'relationship',
            'project_context',
            'risk',
            'decision',
            'note',
        ],
    ],
    'prompt_registry' => [
        'default_category' => 'operations',
        'max_versions_per_slug' => 25,
    ],
    'tool_runtime' => [
        'enabled' => env('INTELLIGENCE_TOOLS_ENABLED', true),
        'default_timeout_seconds' => 10,
        'allow_destructive' => false,
        'safe_stub_tools' => [
            'current_datetime',
            'calculator',
            'platform_status',
            'conversation_summary',
            'document_lookup_stub',
            'erp_lookup_stub',
        ],
    ],
    'model_routing' => [
        'enabled' => true,
        'default_capability' => 'chat',
        'fallback_provider' => 'stub',
        'fallback_model' => 'stub-chat-v1',
    ],
    'streaming' => [
        'enabled' => false,
        'provider' => 'null',
        'emit_debug_chunks' => false,
    ],
    'approval' => [
        'auto_approve_safe_tools' => true,
    ],
];
