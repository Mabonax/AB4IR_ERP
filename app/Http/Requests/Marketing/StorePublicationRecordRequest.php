<?php

namespace App\Http\Requests\Marketing;

use App\Domains\Marketing\Enums\MarketingPublicationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'publication_channel' => ['required', Rule::in(MarketingPublicationChannel::values())],
            'published_at' => ['nullable', 'date'],
            'external_reference' => ['nullable', 'string', 'max:2048'],
            'publication_notes' => ['nullable', 'string', 'max:4000'],
            'metrics.metric_date' => ['nullable', 'date'],
            'metrics.impressions' => ['nullable', 'integer', 'min:0'],
            'metrics.reach' => ['nullable', 'integer', 'min:0'],
            'metrics.engagements' => ['nullable', 'integer', 'min:0'],
            'metrics.clicks' => ['nullable', 'integer', 'min:0'],
            'metrics.sessions' => ['nullable', 'integer', 'min:0'],
            'metrics.conversions' => ['nullable', 'integer', 'min:0'],
            'metrics.followers' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
