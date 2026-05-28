<?php

namespace App\Domains\Facilitators\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilitatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $facilitatorId = $this->route('facilitator');

        return [
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'dob' => 'nullable|date',
            'id_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('facilitators', 'id_number')->ignore($facilitatorId),
            ],
            'address' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('facilitators', 'email')->ignore($facilitatorId),
            ],
            'cell' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:150',
            'province_id' => 'nullable|exists:provinces,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeInputString($this->input('name')),
            'surname' => $this->normalizeInputString($this->input('surname')),
            'dob' => $this->normalizeInputString($this->input('dob')),
            'id_number' => $this->normalizeInputString($this->input('id_number')),
            'address' => $this->normalizeInputString($this->input('address')),
            'email' => $this->normalizeEmailInput($this->input('email')),
            'cell' => $this->normalizeInputString($this->input('cell')),
            'specialization' => $this->normalizeInputString($this->input('specialization')),
            'province_id' => $this->normalizeInputString($this->input('province_id')),
        ]);
    }

    protected function normalizeInputString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizeEmailInput(mixed $value): ?string
    {
        $normalized = $this->normalizeInputString($value);

        return $normalized === null ? null : strtolower($normalized);
    }
}
