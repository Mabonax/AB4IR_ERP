<?php

namespace App\Domains\Members\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MemberAssignment extends Model
{
    protected $fillable = [
        'member_id',
        'assignment_type',
        'assignable_type',
        'assignable_id',
        'member_role',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }
}
