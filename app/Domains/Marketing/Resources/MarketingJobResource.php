<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingJobResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'brief' => $this->brief,
            'job_type' => $this->job_type,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'event_id' => $this->event_id,
            'event_name' => $this->event?->title,
            'creator_name' => $this->creator?->name,
            'creator_department_name' => $this->creatorDepartment?->name,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assignee_name' => $this->assignee?->name,
            'assigned_department_id' => $this->assigned_department_id,
            'assigned_department_name' => $this->assignedDepartment?->name,
            'delivery_notes' => $this->delivery_notes,
            'proof_url' => $this->proof_url,
            'proof_file_name' => $this->proof_file_name,
            'has_proof_file' => filled($this->proof_path),
            'submitted_for_approval_at' => $this->submitted_for_approval_at?->toDateTimeString(),
            'submitted_by_name' => $this->submittedBy?->name,
            'approval_notes' => $this->approval_notes,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'reviewed_by_name' => $this->reviewedBy?->name,
            'returned_for_amendments_at' => $this->returned_for_amendments_at?->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'transaction_state' => $this->closed_at ? 'closed' : 'open',
            'transaction_opened_at' => $this->created_at?->toDateTimeString(),
            'transaction_closed_at' => $this->closed_at?->toDateTimeString(),
            'closed_by_name' => $this->closedBy?->name,
            'documents' => MarketingJobDocumentResource::collection($this->whenLoaded('documents')),
            'comments' => MarketingJobCommentResource::collection($this->whenLoaded('comments')),
            'history' => MarketingJobHistoryResource::collection($this->whenLoaded('history')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'can' => [
                'update_status' => $user?->can('updateStatus', $this->resource) ?? false,
                'comment' => $user?->can('comment', $this->resource) ?? false,
                'upload_document' => $user?->can('uploadDocument', $this->resource) ?? false,
                'reassign' => $user?->can('reassign', $this->resource) ?? false,
                'submit_for_approval' => $user?->can('submitForApproval', $this->resource) ?? false,
                'approve' => $user?->can('approve', $this->resource) ?? false,
                'request_amendments' => $user?->can('requestAmendments', $this->resource) ?? false,
            ],
        ];
    }
}
