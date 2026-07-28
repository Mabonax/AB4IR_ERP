<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\PromptTemplate;

class PromptVersioningService
{
    public function nextVersionForSlug(string $slug): int
    {
        return (int) PromptTemplate::query()->where('slug', $slug)->max('version') + 1;
    }

    public function activate(PromptTemplate $template): PromptTemplate
    {
        PromptTemplate::query()
            ->where('slug', $template->slug)
            ->whereKeyNot($template->id)
            ->update([
                'is_default' => false,
                'status' => 'archived',
            ]);

        $template->forceFill([
            'is_default' => true,
            'status' => 'active',
        ])->save();

        return $template->refresh();
    }
}
