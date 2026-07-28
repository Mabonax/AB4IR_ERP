<?php

namespace Database\Factories;

use App\Domains\Intelligence\Models\ModelRoutingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelRoutingRuleFactory extends Factory
{
    protected $model = ModelRoutingRule::class;

    public function definition(): array
    {
        return [
            'provider' => 'stub',
            'model' => 'stub-chat-v1',
            'capability' => 'chat',
            'priority' => 1,
            'max_context_tokens' => 8000,
            'cost_tier' => 'stub',
            'enabled' => true,
            'fallback_provider' => 'stub',
            'fallback_model' => 'stub-chat-v1',
            'metadata' => [],
        ];
    }
}
