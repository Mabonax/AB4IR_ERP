<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UploadMarketingJobDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'document_kind' => ['required', 'in:supporting,concept,delivery,review_feedback,revised_submission,approval_reference'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx,png,jpg,jpeg,webp,txt,xlsx,xls,ppt,pptx,zip'],
        ];
    }
}
