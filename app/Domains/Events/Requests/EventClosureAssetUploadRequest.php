<?php

namespace App\Domains\Events\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventClosureAssetUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['supporting_document', 'photo'])],
            'description' => 'nullable|string|max:2000',
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg,webp',
        ];
    }
}
