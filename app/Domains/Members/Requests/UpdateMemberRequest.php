<?php

namespace App\Domains\Members\Requests;

use Illuminate\Validation\Rule;

class UpdateMemberRequest extends StoreMemberRequest
{
    public function rules(): array
    {
        $memberId = (int) $this->route('member');
        $rules = parent::rules();
        $rules['id_number'] = ['required', 'string', 'max:255', Rule::unique('members', 'id_number')->ignore($memberId)];

        return $rules;
    }
}
