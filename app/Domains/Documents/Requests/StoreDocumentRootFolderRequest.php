<?php

namespace App\Domains\Documents\Requests;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Stakeholders\Models\Stakeholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRootFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_type' => ['required', Rule::in([
                OrganizationProfile::class,
                Program::class,
                Project::class,
                ProjectLocation::class,
                Beneficiary::class,
                Stakeholder::class,
                StaffDepartment::class,
            ])],
            'owner_id' => ['required', 'integer'],
        ];
    }
}
