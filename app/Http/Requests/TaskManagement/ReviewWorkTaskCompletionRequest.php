<?php

namespace App\Http\Requests\TaskManagement;

use Illuminate\Foundation\Http\FormRequest;

class ReviewWorkTaskCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('manager_review_notes')) {
            $notes = trim((string) $this->input('manager_review_notes', ''));

            $this->merge([
                'manager_review_notes' => $notes === '' ? null : $notes,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'manager_review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $routeName = (string) $this->route()?->getName();

                if ($routeName === 'task-management.tasks.return' && trim((string) $this->input('manager_review_notes', '')) === '') {
                    $validator->errors()->add('manager_review_notes', 'Add manager notes explaining what amendments are required.');
                }
            },
        ];
    }
}
