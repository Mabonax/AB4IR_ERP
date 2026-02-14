<?php

namespace App\Domains\Assets\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'asset_category_id' => $this->asset_category_id,
            'asset_batch_id' => $this->asset_batch_id,
            'asset_code' => $this->asset_code,
            'serial_state' => $this->serial_state,
            'category_name' => $this->category?->name,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'description' => $this->category->description,
            ] : null,
            'staff_member_id' => $this->staff_member_id,
            'staff_member' => $this->staffMember ? [
                'id' => $this->staffMember->id,
                'first_name' => $this->staffMember->first_name,
                'last_name' => $this->staffMember->last_name,
                'full_name' => trim($this->staffMember->first_name.' '.$this->staffMember->last_name),
            ] : null,
            'assigned_to' => $this->currentAssignment?->project
                ? 'Project: '.$this->currentAssignment->project->name
                : ($this->currentAssignment?->staffMember
                    ? 'Staff: '.trim($this->currentAssignment->staffMember->first_name.' '.$this->currentAssignment->staffMember->last_name)
                    : ($this->currentAssignment?->department
                        ? 'Department: '.$this->currentAssignment->department->name
                        : null)),
            'current_assignment' => $this->currentAssignment ? [
                'id' => $this->currentAssignment->id,
                'department_id' => $this->currentAssignment->department_id,
                'department_name' => $this->currentAssignment->department?->name,
                'staff_member_id' => $this->currentAssignment->staff_member_id,
                'staff_member_name' => $this->currentAssignment->staffMember
                    ? trim($this->currentAssignment->staffMember->first_name.' '.$this->currentAssignment->staffMember->last_name)
                    : null,
                'project_id' => $this->currentAssignment->project_id,
                'project_name' => $this->currentAssignment->project?->name,
                'assigned_at' => $this->currentAssignment->assigned_at?->toDateTimeString(),
                'notes' => $this->currentAssignment->notes,
            ] : null,
            'assignment_history' => $this->whenLoaded('assignments', function () {
                return $this->assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'department_name' => $assignment->department?->name,
                        'staff_member_name' => $assignment->staffMember
                            ? trim($assignment->staffMember->first_name.' '.$assignment->staffMember->last_name)
                            : null,
                        'project_name' => $assignment->project?->name,
                        'assigned_at' => $assignment->assigned_at?->toDateTimeString(),
                        'returned_at' => $assignment->returned_at?->toDateTimeString(),
                        'notes' => $assignment->notes,
                    ];
                })->values();
            }),
            'name' => $this->name,
            'type' => $this->type,
            'model_name' => $this->model_name,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
