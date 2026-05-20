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
            'active_maintenance_record' => $this->activeMaintenanceRecord ? [
                'id' => $this->activeMaintenanceRecord->id,
                'support_ticket_id' => $this->activeMaintenanceRecord->support_ticket_id,
                'support_ticket_title' => $this->activeMaintenanceRecord->supportTicket?->title,
                'issue_summary' => $this->activeMaintenanceRecord->issue_summary,
                'maintenance_notes' => $this->activeMaintenanceRecord->maintenance_notes,
                'started_at' => $this->activeMaintenanceRecord->started_at?->toDateTimeString(),
            ] : null,
            'maintenance_history' => $this->whenLoaded('maintenanceRecords', function () {
                return $this->maintenanceRecords->map(fn ($record) => [
                    'id' => $record->id,
                    'support_ticket_id' => $record->support_ticket_id,
                    'support_ticket_title' => $record->supportTicket?->title,
                    'issue_summary' => $record->issue_summary,
                    'maintenance_notes' => $record->maintenance_notes,
                    'status' => $record->status,
                    'started_by_name' => $record->startedBy?->name,
                    'completed_by_name' => $record->completedBy?->name,
                    'started_at' => $record->started_at?->toDateTimeString(),
                    'completed_at' => $record->completed_at?->toDateTimeString(),
                ])->values();
            }),
            'decommission_record' => $this->decommissionRecord ? [
                'id' => $this->decommissionRecord->id,
                'reason' => $this->decommissionRecord->reason,
                'notes' => $this->decommissionRecord->notes,
                'decommissioned_by_name' => $this->decommissionRecord->decommissionedBy?->name,
                'decommissioned_at' => $this->decommissionRecord->decommissioned_at?->toDateTimeString(),
            ] : null,
            'support_tickets' => $this->whenLoaded('supportTickets', function () {
                return $this->supportTickets->map(fn ($ticket) => [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'requester_name' => $ticket->requester?->name,
                    'assignee_name' => $ticket->assignee?->name,
                    'created_at' => $ticket->created_at?->toDateTimeString(),
                ])->values();
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
