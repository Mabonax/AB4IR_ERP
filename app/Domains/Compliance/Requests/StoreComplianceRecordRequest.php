<?php

namespace App\Domains\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplianceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.compliance.manage')
            || $this->user()?->can('domain.organization.manage')
            || false;
    }

    public function rules(): array
    {
        return [
            'organisation_id' => ['required', 'integer', 'exists:organisations,id'],
            'title' => ['required', 'string', 'max:255'],
            'compliance_area' => ['required', 'string', 'max:255'],
            'reference_code' => ['nullable', 'string', 'max:255'],
            'filing_frequency' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'submitted_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
