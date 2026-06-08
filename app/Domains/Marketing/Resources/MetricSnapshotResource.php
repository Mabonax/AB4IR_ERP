<?php

namespace App\Domains\Marketing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetricSnapshotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'metric_date' => $this->metric_date?->format('Y-m-d'),
            'impressions' => $this->impressions,
            'reach' => $this->reach,
            'engagements' => $this->engagements,
            'clicks' => $this->clicks,
            'sessions' => $this->sessions,
            'conversions' => $this->conversions,
            'followers' => $this->followers,
        ];
    }
}
