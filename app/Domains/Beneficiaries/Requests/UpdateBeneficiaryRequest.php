<?php

namespace App\Domains\Beneficiaries\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $beneficiaryId = $this->route('beneficiary')->id ?? null;

        return [
            // Personal info
            'name'     => 'sometimes|string|max:100',
            'surname'  => 'sometimes|string|max:100',
            'dob'      => 'sometimes|date',
            'age'      => 'sometimes|integer|min:0',

            // Identification
            'id_number'=> [
                'sometimes',
                'string',
                'size:13',
                Rule::unique('beneficiaries', 'id_number')->ignore($beneficiaryId),
            ],
            'email'    => [
                'sometimes',
                'email',
                Rule::unique('beneficiaries', 'email')->ignore($beneficiaryId),
            ],
            'phone'    => 'nullable|string|max:20',

            // Demographics
            'gender'   => 'sometimes|in:male,female',

            // Address
            'street_address'   => 'nullable|string|max:255',
            'address_line_2'   => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'location_id'      => 'nullable|exists:locations,id',
            'postal_code'      => 'nullable|string|max:20',

            // Education
            'highest_qualification' => 'nullable|string|max:150',

            // Relations
            'next_of_kin_id'   => 'nullable|exists:next_of_kin,id',
        ];
    }
}
