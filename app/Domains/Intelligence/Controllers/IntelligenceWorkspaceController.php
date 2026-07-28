<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Models\AiTool;
use App\Domains\Intelligence\Models\IntelligenceConversation;
use App\Domains\Intelligence\Models\MemoryRecord;
use App\Domains\Intelligence\Models\ModelRoutingRule;
use App\Domains\Intelligence\Models\PromptTemplate;
use App\Domains\Intelligence\Models\ToolExecutionLog;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class IntelligenceWorkspaceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Agent::class);

        return Inertia::render('Intelligence/Dashboard', [
            'summary' => [
                'agents' => Agent::query()->count(),
                'prompts' => PromptTemplate::query()->count(),
                'memory_records' => MemoryRecord::query()->count(),
                'tools' => AiTool::query()->count(),
                'tool_logs' => ToolExecutionLog::query()->count(),
                'routing_rules' => ModelRoutingRule::query()->count(),
                'conversations' => IntelligenceConversation::query()->count(),
            ],
            'diagnostics' => [
                'default_agent_slug' => config('intelligence.default_agent_slug'),
                'memory_enabled' => config('intelligence.memory.enabled'),
                'streaming_enabled' => config('intelligence.streaming.enabled'),
                'fallback_provider' => config('intelligence.model_routing.fallback_provider'),
                'fallback_model' => config('intelligence.model_routing.fallback_model'),
            ],
        ]);
    }
}
