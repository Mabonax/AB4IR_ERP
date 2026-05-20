<?php

namespace App\Domains\TaskManagement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkTaskResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'context_type' => $this->context_type,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'program_id' => $this->program_id,
            'program_title' => $this->program?->title,
            'creator_user_id' => $this->creator_user_id,
            'creator_name' => $this->creator?->name,
            'creator_department_name' => $this->creatorDepartment?->name,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assignee_name' => $this->assignee?->name,
            'assigned_department_id' => $this->assigned_department_id,
            'assigned_department_name' => $this->assignedDepartment?->name,
            'completion_notes' => $this->completion_notes,
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'comments' => WorkTaskCommentResource::collection($this->whenLoaded('comments')),
            'history' => WorkTaskHistoryResource::collection($this->whenLoaded('history')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'can' => [
                'update_status' => $user?->can('updateStatus', $this->resource) ?? false,
                'comment' => $user?->can('comment', $this->resource) ?? false,
                'reassign' => $user?->can('reassign', $this->resource) ?? false,
            ],
        ];
    }
}
