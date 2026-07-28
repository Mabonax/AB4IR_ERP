<?php

namespace App\Domains\Intelligence\Handlers;

use App\Domains\Intelligence\Contracts\IntelligenceToolHandler;
use App\Domains\Intelligence\DTOs\ToolExecutionResult;
use App\Domains\Organisation\Models\Organisation;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Models\User;

class PlatformStatusToolHandler implements IntelligenceToolHandler
{
    public function handle(array $input, ?User $user = null): ToolExecutionResult
    {
        return new ToolExecutionResult(true, [
            'environment' => app()->environment(),
            'organization_count' => Organisation::query()->count(),
            'program_count' => Program::query()->count(),
            'project_count' => Project::query()->count(),
        ]);
    }
}
