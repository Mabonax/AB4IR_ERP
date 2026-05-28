<?php

namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;

class SubmitWorkTaskReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completion_notes' => ['nullable', 'string'],
            'proof_url' => ['nullable', 'url', 'max:2048'],
            'proof_file' => ['nullable', 'file', 'max:10240'],
            'remove_proof_file' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $notes = trim((string) $this->input('completion_notes', ''));
                $proofUrl = trim((string) $this->input('proof_url', ''));
                $hasFile = $this->hasFile('proof_file');
                $removeFile = filter_var($this->input('remove_proof_file', false), FILTER_VALIDATE_BOOLEAN);

                if ($notes === '' && $proofUrl === '' && ! $hasFile && ! $removeFile) {
                    $validator->errors()->add('completion_notes', 'Add a delivery note, a proof link, or an uploaded proof file before submitting for review.');
                }
            },
        ];
    }
}
