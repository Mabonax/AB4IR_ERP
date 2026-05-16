<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Models\User;
use App\Models\provinces;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdsApplication extends Model
{
    use HasFactory;

    protected $table = 'bds_applications';

    protected $fillable = [
        'full_name',
        'id_number',
        'gender',
        'mobile_number',
        'email',
        'company_name',
        'company_registration_number',
        'position_in_company',
        'majority_shareholding',
        'current_number_of_employees',
        'physical_address',
        'website_address',
        'years_in_operation',
        'province_id',
        'has_business_plan',
        'relevant_skill_set',
        'technology_product_service',
        'technology_stage_of_development',
        'application_date',
        'assessment_status',
        'assessed_by_staff_id',
        'assessed_at',
        'pitch_scheduled_at',
        'pitch_notes',
        'adjudication_result',
        'adjudicated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_business_plan' => 'boolean',
        'application_date' => 'date',
        'assessed_at' => 'datetime',
        'pitch_scheduled_at' => 'datetime',
        'adjudicated_at' => 'datetime',
    ];

    public function province()
    {
        return $this->belongsTo(provinces::class, 'province_id');
    }

    public function assessor()
    {
        return $this->belongsTo(StaffMember::class, 'assessed_by_staff_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function adjudications(): HasMany
    {
        return $this->hasMany(AdjudicationAssessment::class, 'smme_id');
    }

    public function pitchSessionProspects(): HasMany
    {
        return $this->hasMany(BdsPitchSessionProspect::class, 'bds_application_id');
    }
}
