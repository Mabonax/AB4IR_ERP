<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliverableVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_notes' => ['nullable', 'string', 'max:4000'],
            'external_reference' => ['nullable', 'url', 'max:2048'],
            'asset_file' => ['nullable', 'file', 'max:51200'],
        ];
    }
}
