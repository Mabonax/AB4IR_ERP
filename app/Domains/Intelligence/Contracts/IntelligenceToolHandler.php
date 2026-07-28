<?php

namespace App\Domains\Intelligence\Contracts;

use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Models\User;

interface IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult;
}
