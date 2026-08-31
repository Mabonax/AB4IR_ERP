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
            'proof_file' => ['nullable', 'file', 'max:51200'],
            'remove_proof_file' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'proof_file.max' => 'The final task deliverable may not be larger than 50 MB.',
            'proof_file.file' => 'Upload a valid final task deliverable file.',
            'proof_url.url' => 'Enter a valid deliverable link URL.',
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
                $task = $this->route('task');
                $hasExistingFile = $task && filled($task->proof_path) && ! $removeFile;
                $hasDeliverable = $proofUrl !== '' || $hasFile || $hasExistingFile;

                if (! $hasDeliverable) {
                    $validator->errors()->add('proof_file', 'Upload the final task deliverable, or add a deliverable link, before submitting for manager review.');
                }

                if ($notes === '' && ! $hasDeliverable) {
                    $validator->errors()->add('completion_notes', 'Add a short note explaining what is being submitted for review.');
                }
            },
        ];
    }
}
