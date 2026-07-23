<?php

namespace App\Domains\Beneficiaries\Requests;

use App\Domains\Beneficiaries\Models\BeneficiaryOutcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiaryLifecycleActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
            'outcome_type' => ['nullable', Rule::in(BeneficiaryOutcome::TYPES)],
            'outcome_notes' => 'nullable|string|max:2000',
        ];
    }
}
