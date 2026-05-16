<?php

namespace App\Domains\BusinessDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignBdsIncubateeKpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bds_kpi_definition_id' => ['required', 'integer', 'exists:bds_kpi_definitions,id'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'baseline_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
