<?php

namespace App\Domains\Members\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberWorkExperience extends Model
{
    protected $fillable = [
        'member_id',
        'employer',
        'position',
        'industry',
        'start_date',
        'end_date',
        'current_employer_flag',
        'responsibilities',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'current_employer_flag' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
