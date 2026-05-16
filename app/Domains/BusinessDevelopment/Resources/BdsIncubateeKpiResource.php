<?php

namespace App\Domains\BusinessDevelopment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BdsIncubateeKpiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestReview = $this->reviews
            ->sortByDesc('review_date')
            ->first();

        return [
            'id' => $this->id,
            'status' => $this->status,
            'target_value' => $this->target_value,
            'baseline_value' => $this->baseline_value,
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'definition' => [
                'id' => $this->definition?->id,
                'name' => $this->definition?->name,
                'category' => $this->definition?->category,
                'measurement_type' => $this->definition?->measurement_type,
                'unit' => $this->definition?->unit,
                'weight' => $this->definition?->weight,
                'description' => $this->definition?->description,
            ],
            'progress' => [
                'latest_progress_percent' => (int) ($latestReview?->progress_percent ?? 0),
                'latest_actual_value' => $latestReview?->actual_value,
                'latest_status' => $latestReview?->status,
                'risk_state' => match ($latestReview?->status) {
                    'completed' => 'healthy',
                    'on_track' => 'healthy',
                    'needs_attention' => 'warning',
                    'at_risk' => 'critical',
                    default => 'unknown',
                },
            ],
            'latest_review' => $latestReview
                ? new BdsIncubateeKpiReviewResource($latestReview)
                : null,
            'reviews' => BdsIncubateeKpiReviewResource::collection(
                $this->reviews
                    ->sortByDesc('review_date')
                    ->values()
            ),
        ];
    }
}
