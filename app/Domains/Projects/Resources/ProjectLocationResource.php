<?php

namespace App\Domains\Projects\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'facilitator_id' => $this->facilitator_id,
            'facilitator_name' => $this->facilitator
                ? trim($this->facilitator->name.' '.$this->facilitator->surname)
                : null,
            'location' => $this->location,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
