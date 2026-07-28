<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\AiTool;
use Illuminate\Database\Eloquent\Collection;

class ToolRegistry
{
    public function all(): Collection
    {
        return AiTool::query()->orderBy('name')->get();
    }

    public function activeForAgent(array $allowedToolSlugs): Collection
    {
        return AiTool::query()
            ->where('status', 'active')
            ->whereIn('slug', $allowedToolSlugs)
            ->orderBy('name')
            ->get();
    }

    public function findBySlug(string $slug): ?AiTool
    {
        return AiTool::query()->where('slug', $slug)->first();
    }
}
