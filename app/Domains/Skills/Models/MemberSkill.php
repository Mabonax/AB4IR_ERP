<?php

namespace App\Domains\Skills\Models;

use App\Domains\Members\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSkill extends Model
{
    protected $table = 'member_skills';

    protected $fillable = [
        'member_id',
        'skill_name',
        'category',
        'proficiency_level',
        'years_experience',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
