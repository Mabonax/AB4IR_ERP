<?php

namespace App\Domains\Beneficiaries\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // =========================
            // Beneficiary
            // =========================
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'dob' => 'required|date',
            'age' => 'required|integer|min:0',

            'id_number' => 'required|string|size:13|unique:beneficiaries,id_number',
            'email' => 'required|email|unique:beneficiaries,email',
            'phone' => 'nullable|string|max:20',

            'gender' => 'required|in:male,female',
            'project_id' => 'required|exists:projects,id',
            'project_location_id' => 'required|exists:project_locations,id',

            'street_address' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'postal_code' => 'nullable|string|max:20',

            'highest_qualification' => 'nullable|string|max:150',
            'attendance_status' => 'required|in:active,dropout',

            // =========================
            // Next of Kin
            // =========================
            'nok_name' => 'nullable|string|max:100',
            'nok_surname' => 'nullable|string|max:100',
            'nok_relationship' => 'nullable|string|max:100',
            'nok_phone' => 'nullable|string|max:20',
            'nok_email' => 'nullable|email',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasAnyNextOfKinInput()) {
                return;
            }

            foreach ([
                'nok_name' => 'Next of kin name is required when next of kin details are provided.',
                'nok_surname' => 'Next of kin surname is required when next of kin details are provided.',
                'nok_relationship' => 'Next of kin relationship is required when next of kin details are provided.',
            ] as $field => $message) {
                if ($this->filled($field)) {
                    continue;
                }

                $validator->errors()->add($field, $message);
            }
        });
    }

    protected function hasAnyNextOfKinInput(): bool
    {
        foreach (['nok_name', 'nok_surname', 'nok_relationship', 'nok_phone', 'nok_email'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }
}
