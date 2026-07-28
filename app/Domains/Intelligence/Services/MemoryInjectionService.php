<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\Agent;

class MemoryInjectionService
{
    public function __construct(
        protected MemoryRetriever $retriever
    ) {}

    public function inject(string $subjectType, int $subjectId, Agent $agent): array
    {
        if (! $agent->memory_enabled || ! config('intelligence.memory.enabled')) {
            return [];
        }

        return $this->retriever
            ->retrieve($subjectType, $subjectId, $agent, (int) config('intelligence.memory.default_limit', 5))
            ->map(fn ($memory) => [
                'id' => $memory->id,
                'memory_type' => $memory->memory_type,
                'content' => $memory->content,
                'confidence_score' => $memory->confidence_score,
            ])
            ->values()
            ->all();
    }
}
