<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingDeliverableResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'deliverable_type' => $this->deliverable_type,
            'assigned_unit' => $this->assigned_unit,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'review_notes' => $this->review_notes,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'published_at' => $this->published_at?->toDateTimeString(),
            'assignee_name' => $this->assignee?->name,
            'current_version_id' => $this->current_version_id,
            'versions' => DeliverableVersionResource::collection($this->whenLoaded('versions')),
            'assets' => MarketingAssetResource::collection($this->whenLoaded('assets')),
            'can' => [
                'upload_version' => $user?->can('uploadVersion', $this->resource) ?? false,
                'approve' => $user?->can('approve', $this->resource) ?? false,
            ],
        ];
    }
}
