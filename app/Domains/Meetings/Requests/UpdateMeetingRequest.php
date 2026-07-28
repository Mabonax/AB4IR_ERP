<?php

namespace App\Domains\Meetings\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $meetingId = (int) $this->route('meeting');

        return [
            'organisation_id' => ['required', 'integer', 'exists:organisations,id'],
            'committee_id' => ['nullable', 'integer', 'exists:committees,id'],
            'meeting_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('meetings')
                    ->ignore($meetingId)
                    ->where(fn ($query) => $query->where('organisation_id', $this->integer('organisation_id'))),
            ],
            'title' => ['required', 'string', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'minutes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'completed', 'cancelled'])],
        ];
    }
}
