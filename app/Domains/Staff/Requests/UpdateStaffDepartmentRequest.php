<?php

namespace App\Domains\Staff\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('staff_department');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('staff_departments', 'name')->ignore($departmentId),
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }
}
