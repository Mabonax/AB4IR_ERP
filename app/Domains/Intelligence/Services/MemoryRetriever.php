<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\Agent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class MemoryRetriever
{
    public function __construct(
        protected MemoryRepository $repository
    ) {}

    public function retrieve(string $subjectType, int $subjectId, Agent $agent, int $limit = 5): Collection
    {
        $minimumConfidence = (float) config('intelligence.memory.minimum_confidence', 0.55);
        $now = CarbonImmutable::now();

        return $this->repository->baseQuery()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('confidence_score', '>=', $minimumConfidence)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->whereIn('visibility', $this->allowedVisibilities($agent->visibility))
            ->orderByDesc('reviewed_at')
            ->orderByDesc('confidence_score')
            ->limit($limit)
            ->get();
    }

    protected function allowedVisibilities(string $agentVisibility): array
    {
        return match ($agentVisibility) {
            'private' => ['private', 'global'],
            'team' => ['private', 'team', 'global'],
            'organization' => ['private', 'team', 'organization', 'global'],
            default => ['private', 'team', 'organization', 'global'],
        };
    }
}
