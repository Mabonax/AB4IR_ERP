<?php

namespace App\Domains\BusinessDevelopment\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBdsIncubateeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'id_number' => 'required|string|max:100|unique:bds_incubatees,id_number',
            'gender' => 'required|string|max:50',
            'mobile_number' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'company_name' => 'required|string|max:255',
            'company_registration_number' => 'required|string|max:100|unique:bds_incubatees,company_registration_number',
            'position_in_company' => 'nullable|string|max:255',
            'majority_shareholding' => 'nullable|string|max:255',
            'current_number_of_employees' => 'required|integer|min:0',
            'physical_address' => 'nullable|string|max:2000',
            'website_address' => 'nullable|string|max:255',
            'years_in_operation' => 'required|integer|min:0',
            'province_id' => 'required|integer|exists:provinces,id',
            'has_business_plan' => 'required|boolean',
            'relevant_skill_set' => 'required|string|max:5000',
            'technology_product_service' => 'required|string|max:5000',
            'technology_stage_of_development' => 'required|string|max:2000',
            'status' => 'required|in:active,inactive',
            'incubated_date' => 'required|date',
        ];
    }
}
