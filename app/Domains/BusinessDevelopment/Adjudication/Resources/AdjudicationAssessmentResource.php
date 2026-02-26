<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdjudicationAssessmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'platform_name' => $this->platform_name,
            'adjudication_date' => $this->adjudication_date?->toDateString(),
            'development_stage' => $this->development_stage,
            'status' => $this->status,
            'total_score' => (int) $this->total_score,
            'additional_notes' => $this->additional_notes,
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'judge' => [
                'id' => $this->judge?->id,
                'name' => $this->judge?->name,
            ],
            'smme' => [
                'id' => $this->smme?->id,
                'name' => $this->smme?->company_name,
            ],
            'scores' => $this->scores
                ->sortBy(fn ($score) => $score->section?->sort_order ?? PHP_INT_MAX)
                ->values()
                ->map(fn ($score) => [
                    'section_id' => $score->section_id,
                    'score' => (int) $score->score,
                    'comment' => $score->comment,
                ]),
            'sections' => $this->sections
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($section) => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'max_points' => (int) $section->max_points,
                    'sort_order' => (int) $section->sort_order,
                ]),
        ];
    }
}
