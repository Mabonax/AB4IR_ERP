<?php

namespace App\Domains\Intelligence\Contracts;

use App\Domains\Intelligence\DTOs\AgentExecutionResponse;
use App\Domains\Intelligence\Models\Agent;

interface IntelligenceProviderContract
{
    public function respond(Agent $agent, array $payload): AgentExecutionResponse;
}
