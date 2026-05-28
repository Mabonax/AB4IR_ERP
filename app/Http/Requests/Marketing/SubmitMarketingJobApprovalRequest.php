<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMarketingJobApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_notes' => ['nullable', 'string', 'max:8000'],
            'proof_url' => ['nullable', 'url', 'max:2048'],
            'proof_file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,png,jpg,jpeg,webp,txt,xlsx,xls,ppt,pptx,zip'],
            'remove_proof_file' => ['nullable', 'boolean'],
        ];
    }
}
