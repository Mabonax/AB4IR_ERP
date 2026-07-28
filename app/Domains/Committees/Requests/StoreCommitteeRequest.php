<?php

namespace App\Domains\Committees\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommitteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organisation_id' => ['required', 'integer', 'exists:organisations,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('committees')->where(fn ($query) => $query->where('organisation_id', $this->integer('organisation_id'))),
            ],
            'description' => ['nullable', 'string'],
            'chairperson_id' => ['nullable', 'integer', 'exists:users,id'],
            'secretary_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
        ];
    }
}
