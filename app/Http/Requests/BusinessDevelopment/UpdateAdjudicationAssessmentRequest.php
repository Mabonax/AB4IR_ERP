<?php

namespace App\Http\Requests\BusinessDevelopment;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdjudicationAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assessment = $this->route('assessment');

        if (! $assessment instanceof AdjudicationAssessment) {
            return false;
        }

        return $this->user()?->can('update', $assessment) ?? false;
    }

    public function rules(): array
    {
        return [
            'smme_id' => ['required', 'integer', 'exists:bds_applications,id'],
            'pitch_session_id' => ['nullable', 'integer', 'exists:bd_pitch_sessions,id'],
            'platform_name' => ['required', 'string', 'max:255'],
            'adjudication_date' => ['required', 'date'],
            'development_stage' => ['required', Rule::in(['mvp', 'prototype', 'complete_product'])],
            'additional_notes' => ['nullable', 'string'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.section_id' => ['required', 'integer', 'distinct', 'exists:bd_adjudication_sections,id'],
            'scores.*.score' => ['required', 'integer', 'min:0'],
            'scores.*.comment' => ['nullable', 'string'],
        ];
    }
}
