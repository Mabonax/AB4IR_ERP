<?php

namespace App\Domains\Governance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGovernanceStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $structureId = (int) $this->route('structure');

        return [
            'organisation_id' => ['required', 'integer', 'exists:organisations,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('governance_structures')
                    ->ignore($structureId)
                    ->where(fn ($query) => $query->where('organisation_id', $this->integer('organisation_id'))),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
        ];
    }
}
