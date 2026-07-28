<?php

namespace App\Domains\Geography\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGeographyRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('domain.geography.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['municipality', 'region', 'township', 'ward', 'branch'])],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'township_id' => ['nullable', 'integer', 'exists:townships,id'],
            'ward_id' => ['nullable', 'integer', 'exists:wards,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
