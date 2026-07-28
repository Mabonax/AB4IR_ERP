<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\Agent;

class AgentResolver
{
    public function resolve(?string $slug = null): Agent
    {
        $slug ??= config('intelligence.default_agent_slug');

        return Agent::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
