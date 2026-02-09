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
            'serial_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('assets', 'serial_number')->ignore($assetId),
            ],
            'status' => 'required|in:assigned,unassigned,maintenance,retired',
        ];
    }
}
