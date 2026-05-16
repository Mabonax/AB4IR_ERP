<?php

namespace App\Domains\BusinessDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBdsIncubateeKpiReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_date' => ['nullable', 'date'],
            'actual_value' => ['nullable', 'numeric', 'min:0'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['nullable', 'string', 'in:on_track,needs_attention,at_risk,completed'],
            'evidence_notes' => ['nullable', 'string'],
            'mentor_comments' => ['nullable', 'string'],
        ];
    }
}
