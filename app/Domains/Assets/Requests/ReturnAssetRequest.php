<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:3000',
        ];
    }
}

