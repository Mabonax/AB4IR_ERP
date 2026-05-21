<?php

namespace App\Domains\Beneficiaries\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt,xlsx|max:20480',
            'project_id' => 'required|integer|exists:projects,id',
            'project_location_id' => 'required|integer|exists:project_locations,id',
        ];
    }
}
