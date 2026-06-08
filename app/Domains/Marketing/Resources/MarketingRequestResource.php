<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'objective' => $this->objective,
            'description' => $this->description,
            'target_audience' => $this->target_audience,
            'campaign_goal' => $this->campaign_goal,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status,
            'requester_name' => $this->requester?->name,
            'approver_name' => $this->approver?->name,
            'project_name' => $this->project?->name,
            'program_name' => $this->program?->title,
            'event_name' => $this->event?->title,
            'owner_department_name' => $this->ownerDepartment?->name,
            'source_marketing_job_id' => $this->source_marketing_job_id,
            'work_packages' => $this->whenLoaded('workPackages', fn () => $this->workPackages->map(fn ($package) => [
                'id' => $package->id,
                'assigned_unit' => $package->assigned_unit,
                'workload_status' => $package->workload_status,
                'operational_owner_name' => $package->operationalOwner?->name,
                'planned_start_date' => $package->planned_start_date?->format('Y-m-d'),
                'planned_end_date' => $package->planned_end_date?->format('Y-m-d'),
                'actual_end_date' => $package->actual_end_date?->toDateTimeString(),
            ])->values()),
            'deliverables' => MarketingDeliverableResource::collection($this->whenLoaded('deliverables')),
            'activities' => MarketingActivityResource::collection($this->whenLoaded('activities')),
            'comments' => MarketingRequestCommentResource::collection($this->whenLoaded('comments')),
            'documents' => MarketingRequestDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'can' => [
                'view' => $user?->can('view', $this->resource) ?? false,
                'update' => $user?->can('update', $this->resource) ?? false,
                'comment' => $user?->can('comment', $this->resource) ?? false,
                'upload_document' => $user?->can('uploadDocument', $this->resource) ?? false,
            ],
        ];
    }
}
