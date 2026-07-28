<?php

namespace App\Domains\Intelligence\Handlers;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Domains\Intelligence\Models\IntelligenceConversation;
use App\Models\User;

class ConversationSummaryToolHandler implements IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult
    {
        $conversationId = (int) ($input['conversation_id'] ?? 0);
        $conversation = IntelligenceConversation::query()->with('messages')->find($conversationId);

        if (! $conversation) {
            return new ToolExecutionResult(false, [], 'Conversation not found.');
        }

        $messages = $conversation->messages->take(-5)->map(
            fn ($message) => sprintf('%s: %s', $message->role, $message->content)
        )->values()->all();

        return new ToolExecutionResult(true, [
            'conversation_id' => $conversation->id,
            'summary' => implode("\n", $messages),
        ]);
    }
}
