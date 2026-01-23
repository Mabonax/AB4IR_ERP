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
            // =========================
            // Beneficiary
            // =========================
            'name'      => 'required|string|max:100',
            'surname'   => 'required|string|max:100',
            'dob'       => 'required|date',
            'age'       => 'required|integer|min:0',

            'id_number' => 'required|string|size:13|unique:beneficiaries,id_number',
            'email'     => 'required|email|unique:beneficiaries,email',
            'phone'     => 'nullable|string|max:20',

            'gender'    => 'required|in:male,female',

            'street_address' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:100',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'postal_code'    => 'nullable|string|max:20',

            'highest_qualification' => 'nullable|string|max:150',

            // =========================
            // Next of Kin
            // =========================
            'nok_name'         => 'required|string|max:100',
            'nok_surname'      => 'required|string|max:100',
            'nok_relationship' => 'required|string|max:100',
            'nok_phone'        => 'nullable|string|max:20',
            'nok_email'        => 'nullable|email',
        ];
    }
}
