<?php

namespace App\Domains\BusinessDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBdsPitchSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'scheduled_for' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'expected_prospect_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'panelists' => ['required', 'array', 'min:2'],
            'panelists.*' => ['integer', 'distinct', 'exists:users,id'],
            'chair_panelist_id' => ['nullable', 'integer', 'exists:users,id'],
            'prospects' => ['required', 'array', 'min:1'],
            'prospects.*' => ['integer', 'distinct', 'exists:bds_applications,id'],
        ];
    }
}
