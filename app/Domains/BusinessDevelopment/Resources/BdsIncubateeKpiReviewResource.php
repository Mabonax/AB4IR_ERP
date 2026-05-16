<?php

namespace App\Domains\BusinessDevelopment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BdsIncubateeKpiReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'review_date' => $this->review_date?->toDateString(),
            'actual_value' => $this->actual_value,
            'progress_percent' => (int) $this->progress_percent,
            'status' => $this->status,
            'evidence_notes' => $this->evidence_notes,
            'mentor_comments' => $this->mentor_comments,
            'reviewed_by' => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
            ],
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
