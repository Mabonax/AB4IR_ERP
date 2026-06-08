<?php

namespace App\Domains\Organization\Resources;

use App\Domains\Organization\Enums\OrganizationDocumentType;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'document_type' => $this->document_type,
            'document_type_label' => OrganizationDocumentType::tryFrom($this->document_type)?->label() ?? str_replace('_', ' ', $this->document_type),
            'description' => $this->description,
            'audience_scope' => $this->audience_scope,
            'department_name' => $this->department?->name,
            'slot_key' => $this->slot_key,
            'replace_existing' => $this->replace_existing,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from?->toDateTimeString(),
            'effective_until' => $this->effective_until?->toDateTimeString(),
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'published_by_name' => $this->publishedBy?->name,
            'target_user_ids' => $this->whenLoaded('targetUsers', fn () => $this->targetUsers->pluck('id')->values()->all(), []),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'created_at' => $this->created_at?->toDateTimeString(),
            'download_url' => route('organization.documents.download', $this->resource),
            'can' => [
                'download' => $user?->can('view', $this->resource) ?? false,
                'manage' => $user?->can('update', $this->resource) ?? false,
            ],
        ];
    }
}
