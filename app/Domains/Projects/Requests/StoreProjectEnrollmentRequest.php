<?php

namespace App\Domains\Projects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'project_location_id' => 'required|exists:project_locations,id',
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'status' => 'required|in:enrolled,completed,dropped',
            'enrolled_at' => 'nullable|date',
        ];
    }
}
