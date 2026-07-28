<?php

namespace App\Domains\Qualifications\Models;

use App\Domains\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qualification extends Model
{
    protected $table = 'member_qualifications';

    protected $fillable = [
        'member_id',
        'qualification_type',
        'institution',
        'qualification_name',
        'field_of_study',
        'nqf_level',
        'start_date',
        'end_date',
        'completed_flag',
        'completion_year',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_flag' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
