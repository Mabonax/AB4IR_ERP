<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use App\Models\provinces;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_business_plan' => 'boolean',
        'application_date' => 'date',
        'assessed_at' => 'datetime',
        'pitch_scheduled_at' => 'datetime',
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
}
