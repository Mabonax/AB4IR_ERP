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
            'dob' => 'required|date',
            'id_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('facilitators', 'id_number')->ignore($facilitatorId),
            ],
            'address' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('facilitators', 'email')->ignore($facilitatorId),
            ],
            'cell' => 'required|string|max:20',
            'specialization' => 'required|string|max:150',
        ];
    }
}
