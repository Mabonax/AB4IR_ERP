<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completion_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
