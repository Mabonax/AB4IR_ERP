<?php

namespace App\Domains\Facilitators\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilitatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'dob' => 'required|date',
            'id_number' => 'required|string|max:20|unique:facilitators,id_number',
            'address' => 'required|string|max:255',
            'email' => 'required|email|unique:facilitators,email',
            'cell' => 'required|string|max:20',
            'specialization' => 'required|string|max:150',
            'province_id' => 'required|exists:provinces,id',
        ];
    }
}
