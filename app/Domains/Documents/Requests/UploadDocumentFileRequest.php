<?php

namespace App\Domains\Documents\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['required', 'integer', 'exists:document_folders,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:25600'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Choose a document to upload.',
            'file.file' => 'Choose a valid document file.',
            'file.mimes' => 'Only PDF, Word, Excel, and PowerPoint files can be uploaded.',
            'file.max' => 'Documents may not be larger than 25 MB.',
            'folder_id.required' => 'Open a workspace folder before uploading a document.',
            'folder_id.exists' => 'The selected folder could not be found.',
        ];
    }
}
