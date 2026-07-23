<?php

namespace App\Domains\Events\Services;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventHistory;
use App\Models\User;

class EventHistoryService
{
    public function record(
        Event $event,
        string $action,
        string $summary,
        ?User $actor = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $reason = null,
        array $meta = [],
    ): EventHistory {
        return $event->history()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'summary' => $summary,
            'reason' => $reason,
            'meta' => $meta,
        ]);
    }

    public function map(EventHistory $history): array
    {
        return [
            'id' => $history->id,
            'action' => $history->action,
            'from_status' => $history->from_status,
            'to_status' => $history->to_status,
            'summary' => $history->summary,
            'reason' => $history->reason,
            'meta' => $history->meta ?? [],
            'actor_name' => $history->actor?->name,
            'created_at' => $history->created_at?->toDateTimeString(),
        ];
    }
}
