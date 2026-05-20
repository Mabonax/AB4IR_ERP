<?php

namespace App\Domains\Projects\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => 'required|exists:programs,id',
            'sponsor_stakeholder_id' => 'nullable|exists:stakeholders,id',
            'partner_stakeholder_ids' => 'nullable|array',
            'partner_stakeholder_ids.*' => 'integer|exists:stakeholders,id|different:sponsor_stakeholder_id',
            'project_manager_id' => 'required|exists:staff_members,id',
            'contract_reference' => 'nullable|string|max:255',
            'funding_amount' => 'nullable|numeric|min:0',
            'reporting_cadence' => 'nullable|string|max:100',
            'reporting_obligations' => 'nullable|string|max:4000',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planned,active,completed,on_hold,cancelled',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
