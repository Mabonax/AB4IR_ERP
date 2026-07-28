<?php

namespace App\Domains\Employment\Models;

use App\Domains\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentProfile extends Model
{
    protected $table = 'member_employment_profiles';

    protected $fillable = [
        'member_id',
        'employment_status',
        'employer',
        'occupation',
        'industry',
        'years_experience',
        'monthly_income_band',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
