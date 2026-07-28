<?php

namespace App\Domains\Intelligence\Handlers;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Models\User;
use Illuminate\Support\Carbon;

class CurrentDatetimeToolHandler implements IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult
    {
        return new ToolExecutionResult(true, [
            'iso' => Carbon::now()->toIso8601String(),
            'timezone' => config('app.timezone'),
        ]);
    }
}
