<?php

namespace App\Domains\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_summary' => ['required', 'string', 'max:255'],
            'maintenance_notes' => ['nullable', 'string', 'max:3000'],
            'support_ticket_id' => ['nullable', 'integer', 'exists:support_tickets,id'],
        ];
    }
}
