<?php

namespace App\Domains\Documents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'linkable_type' => ['required', 'string', 'max:255'],
            'linkable_id' => ['required', 'integer'],
            'relationship_type' => ['required', 'string', 'max:50'],
        ];
    }
}
