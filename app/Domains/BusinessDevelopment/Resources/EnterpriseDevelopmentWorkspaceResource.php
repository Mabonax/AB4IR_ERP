<?php

namespace App\Domains\BusinessDevelopment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseDevelopmentWorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'overview' => $this->resource['overview'],
            'diagnostics' => $this->resource['diagnostics']->map(fn ($diagnostic) => [
                'id' => $diagnostic->id,
                'assessment_type' => $diagnostic->assessment_type,
                'assessment_date' => $diagnostic->assessment_date?->toDateString(),
                'status' => $diagnostic->status,
                'overall_score' => $diagnostic->overall_score !== null ? (float) $diagnostic->overall_score : null,
                'dimension_scores' => $diagnostic->dimension_scores ?? [],
                'outcome_baseline' => $diagnostic->outcome_baseline ?? [],
                'notes' => $diagnostic->notes,
                'completed_at' => $diagnostic->completed_at?->toDateTimeString(),
                'criteria' => $diagnostic->criteria->map(fn ($criterion) => [
                    'id' => $criterion->id,
                    'criterion_name' => $criterion->criterion_name,
                    'criterion_code' => $criterion->criterion_code,
                    'dimension_name' => $criterion->dimension_name,
                    'dimension_code' => $criterion->dimension_code,
                    'maturity_status' => $criterion->maturity_status,
                    'maturity_score' => $criterion->maturity_score,
                    'evidence_required' => (bool) $criterion->evidence_required,
                    'required' => (bool) $criterion->required,
                    'assessor_observation' => $criterion->assessor_observation,
                    'evidence_document_file_id' => $criterion->evidence_document_file_id,
                    'evidence_label' => $criterion->evidence_label,
                    'verified_at' => $criterion->verified_at?->toDateString(),
                    'expires_at' => $criterion->expires_at?->toDateString(),
                    'evidence_file' => $criterion->evidenceFile ? [
                        'id' => $criterion->evidenceFile->id,
                        'title' => $criterion->evidenceFile->title,
                        'original_name' => $criterion->evidenceFile->original_name,
                    ] : null,
                ])->values(),
                'gaps' => $diagnostic->gaps->values(),
            ])->values(),
            'open_gaps' => $this->resource['open_gaps']->values(),
            'needs' => $this->resource['needs']->values(),
            'plans' => $this->resource['plans']->values(),
            'history' => $this->resource['history']->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'title' => $event->title,
                'details' => $event->details,
                'actor' => $event->actor?->name,
                'occurred_at' => $event->occurred_at?->toDateTimeString(),
            ])->values(),
        ];
    }
}
