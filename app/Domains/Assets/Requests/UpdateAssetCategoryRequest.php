<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('asset_category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'name')->ignore($categoryId),
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }
}
