<?php

namespace App\Domains\TaskManagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkTaskHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'actor_name' => $this->actor?->name,
            'action' => $this->action,
            'summary' => $this->summary,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
