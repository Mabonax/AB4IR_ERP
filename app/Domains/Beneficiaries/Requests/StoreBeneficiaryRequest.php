<?php

namespace App\Domains\Beneficiaries\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Personal info
            'name'     => 'required|string|max:100',
            'surname'  => 'required|string|max:100',
            'dob'      => 'required|date',
            'age'      => 'required|integer|min:0',

            // Identification
            'id_number'=> 'required|string|size:13|unique:beneficiaries,id_number',
            'email'    => 'required|email|unique:beneficiaries,email',
            'phone'    => 'nullable|string|max:20',

            // Demographics
            'gender'   => 'required|in:male,female',

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
