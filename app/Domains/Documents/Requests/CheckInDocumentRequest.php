<?php

namespace App\Domains\Documents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
