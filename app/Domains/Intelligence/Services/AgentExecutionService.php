<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\DTOs\AgentExecutionResponse;
use App\Domains\Intelligence\Enums\ModelCapability;
use App\Domains\Intelligence\Models\IntelligenceConversation;
use App\Models\User;

class AgentExecutionService
{
    public function __construct(
        protected ModelRouter $router,
        protected MemoryInjectionService $memoryInjectionService,
        protected ToolRegistry $toolRegistry,
        protected ToolExecutor $toolExecutor,
        protected NullIntelligenceProvider $provider,
    ) {}

    public function execute(
        IntelligenceConversation $conversation,
        string $message,
        ?User $user = null
    ): AgentExecutionResponse {
        $agent = $conversation->agent;
        $memory = $conversation->subject_type && $conversation->subject_id
            ? $this->memoryInjectionService->inject($conversation->subject_type, $conversation->subject_id, $agent)
            : [];

        $route = $this->router->route(ModelCapability::ToolUse);
        $tools = $this->toolRegistry->activeForAgent($agent->allowed_tools ?? []);
        $toolOutputs = [];

        foreach ($tools->take(2) as $tool) {
            $toolOutputs[] = $this->toolExecutor->execute(
                $tool,
                $this->defaultToolInput($tool->slug, $conversation->id, $message),
                $user,
                $agent,
                $conversation->id
            )->toArray();
        }

        return $this->provider->respond($agent, [
            'provider' => $route['provider'],
            'model' => $route['model'],
            'user_message' => $message,
            'memory' => $memory,
            'tool_outputs' => $toolOutputs,
        ]);
    }

    protected function defaultToolInput(string $slug, int $conversationId, string $message): array
    {
        return match ($slug) {
            'conversation_summary' => ['conversation_id' => $conversationId],
            'calculator' => ['left' => 2, 'right' => 2, 'operator' => '+'],
            default => ['message' => $message],
        };
    }
}
