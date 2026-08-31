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
            'proof_url' => $this->proof_url,
            'proof_file_name' => $this->proof_file_name,
            'proof_mime_type' => $this->proof_mime_type,
            'proof_file_size' => $this->proof_file_size,
            'has_proof_file' => filled($this->proof_path),
            'proof_download_url' => filled($this->proof_path) ? route('task-management.tasks.proof', $this->resource) : null,
            'proof_preview_url' => filled($this->proof_path) ? route('task-management.tasks.proof.preview', $this->resource) : null,
            'can_preview_proof' => filled($this->proof_path) && $this->isPreviewableFile($this->proof_mime_type, $this->proof_file_name),
            'submitted_for_review_at' => $this->submitted_for_review_at?->toDateTimeString(),
            'submitted_by_name' => $this->submittedBy?->name,
            'manager_review_notes' => $this->manager_review_notes,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'reviewed_by_name' => $this->reviewedBy?->name,
            'returned_for_amendments_at' => $this->returned_for_amendments_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'transaction_state' => $this->closed_at ? 'closed' : 'open',
            'transaction_opened_at' => $this->created_at?->toDateTimeString(),
            'transaction_closed_at' => $this->closed_at?->toDateTimeString(),
            'closed_by_name' => $this->closedBy?->name,
            'documents' => WorkTaskDocumentResource::collection($this->whenLoaded('documents')),
            'comments' => WorkTaskCommentResource::collection($this->whenLoaded('comments')),
            'history' => WorkTaskHistoryResource::collection($this->whenLoaded('history')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'can' => [
                'update_status' => $user?->can('updateStatus', $this->resource) ?? false,
                'comment' => $user?->can('comment', $this->resource) ?? false,
                'reassign' => $user?->can('reassign', $this->resource) ?? false,
                'submit_for_review' => $user?->can('submitForReview', $this->resource) ?? false,
                'approve_completion' => $user?->can('approveCompletion', $this->resource) ?? false,
                'return_for_amendments' => $user?->can('returnForAmendments', $this->resource) ?? false,
                'upload_document' => $user?->can('comment', $this->resource) ?? false,
            ],
        ];
    }

    protected function isPreviewableFile(?string $mimeType, ?string $fileName): bool
    {
        $extension = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
        $mimeType = (string) $mimeType;

        return str_contains($mimeType, 'pdf')
            || str_starts_with($mimeType, 'image/')
            || str_starts_with($mimeType, 'text/')
            || in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'txt', 'md', 'csv'], true);
    }
}
