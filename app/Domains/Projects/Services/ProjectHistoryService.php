<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectHistory;
use App\Models\User;

class ProjectHistoryService
{
    public function record(Project $project, string $action, string $summary, ?User $actor = null, array $meta = []): ProjectHistory
    {
        return $project->history()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'summary' => $summary,
            'meta' => $meta,
        ]);
    }

    public function map(ProjectHistory $history): array
    {
        return [
            'id' => $history->id,
            'action' => $history->action,
            'summary' => $history->summary,
            'meta' => $history->meta ?? [],
            'actor_name' => $history->actor?->name,
            'created_at' => $history->created_at?->toDateTimeString(),
        ];
    }
}
