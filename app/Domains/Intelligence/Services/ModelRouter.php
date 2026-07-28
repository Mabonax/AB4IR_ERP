<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Enums\ModelCapability;
use App\Domains\Intelligence\Models\ModelRoutingRule;

class ModelRouter
{
    public function route(ModelCapability|string $capability): array
    {
        $capabilityValue = $capability instanceof ModelCapability ? $capability->value : $capability;

        $rule = ModelRoutingRule::query()
            ->where('capability', $capabilityValue)
            ->where('enabled', true)
            ->orderBy('priority')
            ->first();

        if ($rule) {
            return [
                'provider' => $rule->provider,
                'model' => $rule->model,
                'fallback_provider' => $rule->fallback_provider ?: config('intelligence.model_routing.fallback_provider'),
                'fallback_model' => $rule->fallback_model ?: config('intelligence.model_routing.fallback_model'),
                'max_context_tokens' => $rule->max_context_tokens,
                'cost_tier' => $rule->cost_tier,
            ];
        }

        return [
            'provider' => config('intelligence.model_routing.fallback_provider'),
            'model' => config('intelligence.model_routing.fallback_model'),
            'fallback_provider' => config('intelligence.model_routing.fallback_provider'),
            'fallback_model' => config('intelligence.model_routing.fallback_model'),
            'max_context_tokens' => 8000,
            'cost_tier' => 'stub',
        ];
    }
}
