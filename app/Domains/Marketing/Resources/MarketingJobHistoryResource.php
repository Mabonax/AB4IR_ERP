<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingJobHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'actor_name' => $this->actor?->name,
            'action' => $this->action,
            'summary' => $this->summary,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
