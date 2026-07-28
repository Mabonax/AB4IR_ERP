<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\DTOs\AgentExecutionResponse;
use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Models\IntelligenceConversation;
use App\Domains\Intelligence\Models\IntelligenceMessage;
use App\Models\User;
use Illuminate\Support\Carbon;

class ConversationManager
{
    public function __construct(
        protected AgentExecutionService $agentExecutionService,
        protected AgentResolver $agentResolver
    ) {}

    public function run(array $payload, User $user): array
    {
        $conversation = $this->resolveConversation($payload, $user);

        IntelligenceMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $payload['message'],
            'provider' => null,
            'model' => null,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'metadata' => [],
        ]);

        $response = $this->agentExecutionService->execute($conversation, (string) $payload['message'], $user);

        $this->storeAssistantMessage($conversation, $response);

        $conversation->forceFill([
            'last_message_at' => Carbon::now(),
        ])->save();

        return [
            'conversation' => $conversation->fresh(['agent', 'messages']),
            'response' => $response->toArray(),
        ];
    }

    protected function resolveConversation(array $payload, User $user): IntelligenceConversation
    {
        if (! empty($payload['conversation_id'])) {
            return IntelligenceConversation::query()->with('agent')->findOrFail($payload['conversation_id']);
        }

        /** @var Agent $agent */
        $agent = $this->agentResolver->resolve($payload['agent_slug'] ?? null);

        return IntelligenceConversation::query()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'subject_type' => $payload['subject_type'] ?? 'organization',
            'subject_id' => (int) ($payload['subject_id'] ?? 1),
            'title' => str((string) $payload['message'])->limit(60),
            'status' => 'active',
            'last_message_at' => Carbon::now(),
            'metadata' => [],
        ])->load('agent');
    }

    protected function storeAssistantMessage(IntelligenceConversation $conversation, AgentExecutionResponse $response): void
    {
        IntelligenceMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response->content,
            'provider' => $response->provider,
            'model' => $response->model,
            'prompt_tokens' => (int) ($response->usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($response->usage['completion_tokens'] ?? 0),
            'metadata' => $response->metadata,
        ]);
    }
}
