<?php

namespace App\Domains\Intelligence\Handlers;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Models\User;

class ErpLookupStubToolHandler implements IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult
    {
        return new ToolExecutionResult(true, [
            'records' => [],
            'note' => 'ERP lookup remains stubbed in Phase 3 to avoid unsafe broad data access.',
        ]);
    }
}
