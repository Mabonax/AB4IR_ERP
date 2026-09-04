<?php

namespace App\Domains\Events\Requests;

use App\Domains\Events\Models\EventSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEventSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EventSeries::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'series_key' => ['nullable', 'string', 'max:255', Rule::unique('event_series', 'series_key')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('event_series', 'slug')],
            'description' => ['nullable', 'string', 'max:4000'],
            'objectives' => ['nullable', 'string', 'max:4000'],
            'default_title_pattern' => ['nullable', 'string', 'max:255'],
            'default_event_type' => ['nullable', 'string', 'max:255'],
            'default_format' => ['nullable', 'in:physical,virtual,hybrid'],
            'default_theme' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([EventSeries::STATUS_ACTIVE, EventSeries::STATUS_INACTIVE])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name');

        $this->merge([
            'series_key' => $this->filled('series_key') ? Str::slug((string) $this->input('series_key')) : Str::slug($name),
            'slug' => $this->filled('slug') ? Str::slug((string) $this->input('slug')) : Str::slug($this->input('series_key') ?: $name),
            'default_title_pattern' => $this->input('default_title_pattern') ?: ($name ? $name.' {year}' : null),
        ]);
    }
}
