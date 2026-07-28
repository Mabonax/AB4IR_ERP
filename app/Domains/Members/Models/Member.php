<?php

namespace App\Domains\Members\Models;

use App\Domains\Employment\Models\EmploymentProfile;
use App\Domains\Geography\Models\Branch;
use App\Domains\Geography\Models\Municipality;
use App\Domains\Geography\Models\Region;
use App\Domains\Geography\Models\Township;
use App\Domains\Geography\Models\Ward;
use App\Domains\Qualifications\Models\Qualification;
use App\Domains\Skills\Models\MemberSkill;
use App\Models\Provinces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'id_number',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'physical_address',
        'province_id',
        'municipality_id',
        'region_id',
        'township_id',
        'ward_id',
        'branch_id',
        'member_type',
        'status',
        'disability_status',
        'youth_indicator',
        'veteran_indicator',
        'household_size',
        'dependants',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'disability_status' => 'boolean',
        'youth_indicator' => 'boolean',
        'veteran_indicator' => 'boolean',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function township(): BelongsTo
    {
        return $this->belongsTo(Township::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employmentProfile(): HasOne
    {
        return $this->hasOne(EmploymentProfile::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(MemberSkill::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(MemberWorkExperience::class);
    }

    public function opportunityInterests(): HasMany
    {
        return $this->hasMany(OpportunityInterest::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MemberAssignment::class);
    }
}
