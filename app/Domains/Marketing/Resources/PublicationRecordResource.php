<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicationRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'publication_channel' => $this->publication_channel,
            'published_by_name' => $this->publisher?->name,
            'published_at' => $this->published_at?->toDateTimeString(),
            'external_reference' => $this->external_reference,
            'publication_notes' => $this->publication_notes,
            'metrics' => MetricSnapshotResource::collection($this->whenLoaded('metricSnapshots')),
        ];
    }
}
