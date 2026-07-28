<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\PromptTemplate;
use Illuminate\Database\Eloquent\Collection;

class PromptTemplateRepository
{
    public function all(): Collection
    {
        return PromptTemplate::query()->with('owner')->orderBy('slug')->orderByDesc('version')->get();
    }

    public function find(int $id): ?PromptTemplate
    {
        return PromptTemplate::query()->find($id);
    }

    public function activeBySlug(string $slug): ?PromptTemplate
    {
        return PromptTemplate::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->orderByDesc('version')
            ->first();
    }
}
