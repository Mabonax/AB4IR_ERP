<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingAssetResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'asset_type' => $this->asset_type,
            'asset_file_name' => $this->asset_file_name,
            'reusable' => $this->reusable,
            'archived_at' => $this->archived_at?->toDateTimeString(),
            'deliverable_id' => $this->deliverable_id,
            'deliverable_title' => $this->deliverable?->title,
            'version_number' => $this->version?->version_number,
            'publications' => PublicationRecordResource::collection($this->whenLoaded('publications')),
            'can' => [
                'publish' => $user?->can('publish', $this->resource) ?? false,
                'archive' => $user?->can('archive', $this->resource) ?? false,
                'publish_to_vault' => $user?->can('create', \App\Domains\Organization\Models\OrganizationDocument::class) ?? false,
            ],
        ];
    }
}
