<?php

namespace App\Domains\BusinessDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBdsPitchSessionProspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_decision' => ['required', 'in:incubated,rejected'],
            'manager_notes' => ['nullable', 'string'],
        ];
    }
}
