<?php

namespace App\Domains\Projects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'facilitator_id' => 'required|exists:facilitators,id',
            'province_id' => 'required|exists:provinces,id',
            'training_venue_address' => 'nullable|string|max:255',
        ];
    }
}
