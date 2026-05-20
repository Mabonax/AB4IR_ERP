<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\Provinces;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdsIncubatee extends Model
{
    use HasFactory;

    protected $table = 'bds_incubatees';

    protected $fillable = [
        'bds_application_id',
        'intake_type',
        'intake_source',
        'intake_justification',
        'intake_approved_at',
        'intake_approved_by',
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
        'status',
        'incubated_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_business_plan' => 'boolean',
        'incubated_date' => 'date',
        'intake_approved_at' => 'datetime',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function intakeApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intake_approved_by');
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(BdsIncubateeKpi::class, 'bds_incubatee_id');
    }
}
