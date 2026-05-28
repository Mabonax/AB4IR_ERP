<?php

namespace App\Domains\TaskManagement\Resources;

use App\Domains\TaskManagement\Services\SupportTicketService;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $sla = app(SupportTicketService::class)->slaStatus($this->resource);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'support_area' => $this->support_area,
            'requester_user_id' => $this->requester_user_id,
            'requester_name' => $this->requester?->name,
            'requester_department_name' => $this->requesterDepartment?->name,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assignee_name' => $this->assignee?->name,
            'assigned_department_name' => $this->assignedDepartment?->name,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'program_id' => $this->program_id,
            'program_title' => $this->program?->title,
            'asset_id' => $this->asset_id,
            'asset_name' => $this->asset?->name,
            'asset_code' => $this->asset?->asset_code,
            'asset_category_name' => $this->asset?->category?->name,
            'resolution_summary' => $this->resolution_summary,
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'first_responded_at' => $this->first_responded_at?->toDateTimeString(),
            'closed_at' => $this->closed_at?->toDateTimeString(),
            'first_response_hours' => $sla['first_response_hours'],
            'age_hours' => $sla['age_hours'],
            'sla_target_hours' => $sla['sla_target_hours'],
            'is_overdue' => $sla['is_overdue'],
            'sla_status' => $sla['sla_status'],
            'replies' => SupportTicketReplyResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'can' => [
                'assign' => $user?->can('assign', $this->resource) ?? false,
                'reply' => $user?->can('reply', $this->resource) ?? false,
                'resolve' => $user?->can('resolve', $this->resource) ?? false,
                'close' => $user?->can('close', $this->resource) ?? false,
                'reopen' => $user?->can('reopen', $this->resource) ?? false,
            ],
        ];
    }
}
