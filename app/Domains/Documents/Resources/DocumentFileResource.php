<?php

namespace App\Domains\Documents\Resources;

use App\Domains\Documents\Models\DocumentApproval;
use App\Domains\Documents\Models\DocumentLink;
use App\Domains\Documents\Models\DocumentVersion;
use App\Domains\Documents\Services\DocumentPreviewService;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentFileResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $latestApproval = $this->approvals->first();

        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'title' => $this->title,
            'description' => $this->description,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'version' => $this->version,
            'status' => $this->status,
            'uploaded_by_name' => $this->uploader?->name,
            'checked_out_by_name' => $this->checkedOutBy?->name,
            'checked_out_at' => $this->checked_out_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'download_url' => route('organization.document-library.files.download', $this->resource),
            'preview_url' => route('organization.document-library.files.preview', $this->resource),
            'preview' => app(DocumentPreviewService::class)->describe($this->resource),
            'versions' => $this->versions->map(fn (DocumentVersion $version) => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'original_name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size_bytes' => $version->size_bytes,
                'notes' => $version->notes,
                'uploaded_by_name' => $version->uploader?->name,
                'created_at' => $version->created_at?->toDateTimeString(),
            ])->values()->all(),
            'links' => $this->links->map(fn (DocumentLink $link) => [
                'id' => $link->id,
                'relationship_type' => $link->relationship_type,
                'linked_at' => $link->created_at?->toDateTimeString(),
                'linkable_type' => class_basename((string) $link->linkable_type),
                'linkable_name' => $link->linkable?->title
                    ?? $link->linkable?->name
                    ?? $link->linkable?->organization_name
                    ?? $link->linkable?->asset_file_name
                    ?? ($link->linkable && isset($link->linkable->first_name)
                        ? trim($link->linkable->first_name.' '.$link->linkable->last_name)
                        : 'Linked Record #'.$link->linkable_id),
            ])->values()->all(),
            'latest_approval' => $latestApproval ? [
                'status' => $latestApproval->status,
                'comments' => $latestApproval->comments,
                'approver_name' => $latestApproval->approver?->name,
                'approved_at' => $latestApproval->approved_at?->toDateTimeString(),
            ] : null,
            'approval_history' => $this->approvals->map(fn (DocumentApproval $approval) => [
                'id' => $approval->id,
                'status' => $approval->status,
                'comments' => $approval->comments,
                'approver_name' => $approval->approver?->name,
                'approved_at' => $approval->approved_at?->toDateTimeString(),
                'created_at' => $approval->created_at?->toDateTimeString(),
            ])->values()->all(),
            'activity' => $this->activityLogs->take(10)->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user_name' => $log->user?->name,
                'entity_context' => $log->entity_context,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])->values()->all(),
            'can' => [
                'download' => $user?->can('view', $this->resource) ?? false,
                'manage' => $user?->can('update', $this->resource) ?? false,
                'version' => $user?->can('version', $this->resource) ?? false,
                'approve' => $user?->can('approve', $this->resource) ?? false,
                'checkout' => $user?->can('checkout', $this->resource) ?? false,
            ],
        ];
    }
}
