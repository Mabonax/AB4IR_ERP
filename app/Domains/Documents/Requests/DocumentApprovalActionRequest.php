<?php

namespace App\Domains\Documents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
