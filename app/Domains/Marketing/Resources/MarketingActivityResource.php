<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'summary' => $this->summary,
            'actor_name' => $this->actor?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
            'meta' => $this->meta,
        ];
    }
}
