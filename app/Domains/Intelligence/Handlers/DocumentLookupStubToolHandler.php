<?php

namespace App\Domains\Intelligence\Handlers;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Models\User;

class DocumentLookupStubToolHandler implements IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult
    {
        return new ToolExecutionResult(true, [
            'matches' => [],
            'note' => 'Document lookup remains stubbed in Phase 3. Integrate the document library in Phase 4.',
        ]);
    }
}
