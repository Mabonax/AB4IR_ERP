<?php

namespace App\Domains\Projects\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'program_title' => $this->program?->title,
            'sponsor_stakeholder_id' => $this->sponsor_stakeholder_id,
            'sponsor_name' => $this->sponsor
                ? trim($this->sponsor->organization_name.' - '.$this->sponsor->name)
                : null,
            'project_manager_id' => $this->project_manager_id,
            'project_manager_name' => $this->projectManager
                ? trim($this->projectManager->first_name.' '.$this->projectManager->last_name)
                : null,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
