<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecommissionAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
