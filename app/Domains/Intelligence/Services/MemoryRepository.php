<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\MemoryRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MemoryRepository
{
    public function baseQuery(): Builder
    {
        return MemoryRecord::query();
    }

    public function forSubject(string $subjectType, int $subjectId): Collection
    {
        return $this->baseQuery()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('confidence_score')
            ->orderByDesc('id')
            ->get();
    }
}
