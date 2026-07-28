<?php

namespace App\Domains\Intelligence\Handlers;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Models\User;

class CalculatorToolHandler implements IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult
    {
        $left = (float) ($input['left'] ?? 0);
        $right = (float) ($input['right'] ?? 0);
        $operator = (string) ($input['operator'] ?? '+');

        $result = match ($operator) {
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right === 0.0 ? null : $left / $right,
            default => $left + $right,
        };

        return new ToolExecutionResult($result !== null, [
            'left' => $left,
            'right' => $right,
            'operator' => $operator,
            'result' => $result,
        ], $result === null ? 'Division by zero is not allowed.' : null);
    }
}
