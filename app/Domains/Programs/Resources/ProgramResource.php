<?php

namespace App\Domains\Programs\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'description' => $this->description,
            'strategic_objective' => $this->strategic_objective,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'budget' => $this->budget !== null ? (float) $this->budget : null,
            'funding_source' => $this->funding_source,
            'responsible_committee_id' => $this->responsible_committee_id,
            'responsible_committee_name' => $this->responsibleCommittee?->name,
            'programme_manager_id' => $this->programme_manager_id,
            'programme_manager_name' => $this->programmeManager
                ? trim($this->programmeManager->first_name.' '.$this->programmeManager->last_name)
                : null,
            'slug' => $this->slug,
            'projects_count' => $this->whenCounted('projects'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
