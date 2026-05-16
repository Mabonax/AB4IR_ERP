<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdjudicationAssessmentListResource extends JsonResource
{
    public function toArray($request): array
    {
        $decisionStatus = match ($this->smme?->adjudication_result) {
            'incubated' => 'accepted',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return [
            'id' => $this->id,
            'platform_name' => $this->platform_name,
            'adjudication_date' => $this->adjudication_date?->toDateString(),
            'development_stage' => $this->development_stage,
            'status' => $this->status,
            'decision_status' => $decisionStatus,
            'total_score' => (int) $this->total_score,
            'judge' => [
                'id' => $this->judge?->id,
                'name' => $this->judge?->name,
            ],
            'smme' => [
                'id' => $this->smme?->id,
                'name' => $this->smme?->company_name,
            ],
        ];
    }
}
