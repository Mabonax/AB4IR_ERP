<?php

namespace App\Domains\Intelligence\Services;

class MemoryExtractor
{
    public function extract(string $content): array
    {
        return collect(preg_split('/[.!?]+/', $content))
            ->map(fn (?string $sentence) => trim((string) $sentence))
            ->filter(fn (string $sentence) => str($sentence)->length() > 18)
            ->take(2)
            ->map(fn (string $sentence) => [
                'memory_type' => 'note',
                'content' => $sentence,
                'confidence_score' => 0.6,
            ])
            ->values()
            ->all();
    }
}
