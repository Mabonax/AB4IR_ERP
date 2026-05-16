<?php

namespace App\Domains\Projects\Resources;

use App\Domains\Projects\Services\ProjectService;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        $statusSummary = app(ProjectService::class)->getStatusSummary($this->resource);

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
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $statusSummary['current_label'],
            'status_summary' => $statusSummary,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
