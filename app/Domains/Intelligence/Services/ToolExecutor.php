<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Domains\Intelligence\Exceptions\ToolExecutionException;
use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Models\AiTool;
use App\Domains\Intelligence\Models\ToolExecutionLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ToolExecutor
{
    public function __construct(
        protected ToolApprovalService $approvalService
    ) {}

    public function execute(AiTool $tool, array $input, ?User $user = null, ?Agent $agent = null, ?int $conversationId = null): ToolExecutionResult
    {
        $started = microtime(true);
        $approved = $this->approvalService->approved($tool, $user);

        if (! $approved) {
            $this->log($tool, $input, [], 'blocked', false, $user, $agent, $conversationId, 'Tool approval required.', $started);
            throw new ToolExecutionException('Tool approval required.');
        }

        $handler = app($tool->handler_class);

        if (! $handler instanceof IntelligenceToolHandler) {
            throw new ToolExecutionException("Invalid handler for tool [{$tool->slug}].");
        }

        $result = $handler->handle($input, $user);
        $this->log($tool, $input, $result->toArray(), $result->success ? 'success' : 'failed', true, $user, $agent, $conversationId, $result->message, $started);

        return $result;
    }

    protected function log(
        AiTool $tool,
        array $input,
        array $output,
        string $status,
        bool $approved,
        ?User $user,
        ?Agent $agent,
        ?int $conversationId,
        ?string $errorMessage,
        float $started
    ): void {
        ToolExecutionLog::query()->create([
            'ai_tool_id' => $tool->id,
            'agent_id' => $agent?->id,
            'conversation_id' => $conversationId,
            'user_id' => $user?->id,
            'status' => $status,
            'input_payload' => $input,
            'output_payload' => $output,
            'error_message' => $errorMessage,
            'approved' => $approved,
            'executed_at' => Carbon::now(),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'metadata' => [],
        ]);
    }
}
