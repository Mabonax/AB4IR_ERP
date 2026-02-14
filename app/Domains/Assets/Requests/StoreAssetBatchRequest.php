<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'type' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1|max:5000',
            'serial_state' => ['required', Rule::in(['pending', 'no_serial'])],
            'notes' => 'nullable|string|max:3000',
        ];
    }
}
