<?php

namespace App\Domains\Programs\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'slug' => 'nullable|string|max:255|unique:programs,slug',
        ];
    }
}
