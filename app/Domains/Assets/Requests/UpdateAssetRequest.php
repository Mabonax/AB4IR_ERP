<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('asset');

        return [
            'asset_category_id' => 'required|exists:asset_categories,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
            'serial_state' => ['required', Rule::in(['recorded', 'pending', 'no_serial'])],
            'serial_number' => [
                Rule::requiredIf(fn () => $this->input('serial_state') === 'recorded'),
                'nullable',
                'string',
                'max:255',
                Rule::unique('assets', 'serial_number')->ignore($assetId),
            ],
            'status' => 'required|in:assigned,unassigned,maintenance,retired',
        ];
    }
}
