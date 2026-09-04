<?php

namespace App\Domains\Events\Requests;

use App\Domains\Events\Models\EventSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventSeriesAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $series = $this->route('eventSeries');

        return $series instanceof EventSeries && ($this->user()?->can('update', $series) ?? false);
    }

    public function rules(): array
    {
        return [
            'document_file_id' => ['required', 'integer', 'exists:document_files,id'],
            'asset_type' => ['required', Rule::in([
                'logo',
                'brand_guideline',
                'historical_poster',
                'reusable_artwork',
                'sponsor_material',
                'programme_template',
                'media',
                'other',
            ])],
            'label' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'is_featured' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
