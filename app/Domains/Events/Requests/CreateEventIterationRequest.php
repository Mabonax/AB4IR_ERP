<?php

namespace App\Domains\Events\Requests;

use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventIterationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) ?? false;
    }

    public function rules(): array
    {
        $series = $this->route('eventSeries');
        $seriesId = $series instanceof EventSeries ? $series->id : null;

        return [
            'event_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('events', 'event_year')->where(fn ($query) => $query->where('event_series_id', $seriesId)),
            ],
            'source' => ['required', Rule::in(['series_defaults', 'latest_iteration', 'selected_iteration'])],
            'source_event_id' => ['nullable', 'required_if:source,selected_iteration', 'integer', Rule::exists('events', 'id')->where(fn ($query) => $query->where('event_series_id', $seriesId))],
            'title' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'copy_partners' => ['nullable', 'boolean'],
            'copy_workstreams' => ['nullable', 'boolean'],
            'copy_task_templates' => ['nullable', 'boolean'],
        ];
    }
}
