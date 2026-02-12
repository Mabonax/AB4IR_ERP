<?php

namespace App\Domains\Staff\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(Arr::undot($this->all()));
    }

    public function rules(): array
    {
        $staffId = $this->route('staff');

        return [
            'staff.first_name' => 'required|string|max:255',
            'staff.last_name' => 'required|string|max:255',
            'staff.email' => [
                'required',
                'email',
                Rule::unique('staff_members', 'email')->ignore($staffId),
            ],
            'staff.phone' => 'nullable|string|max:20',
            'staff.employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('staff_members', 'employee_number')->ignore($staffId),
            ],
            'staff.start_date' => 'required|date',
            'staff.status' => 'required|in:active,inactive',
            'staff.department_id' => 'required|exists:staff_departments,id',
            'staff.manager_id' => 'nullable|exists:staff_members,id',
            'staff.is_ceo' => 'nullable|boolean',
            'staff.is_board_member' => 'nullable|boolean',
            'staff.user_id' => 'nullable|exists:users,id',

            'next_of_kin.full_name' => 'required|string|max:255',
            'next_of_kin.relationship' => 'required|string|max:100',
            'next_of_kin.phone' => 'required|string|max:20',
            'next_of_kin.email' => 'nullable|email',
        ];
    }
}
