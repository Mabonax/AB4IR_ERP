<?php

namespace App\Domains\Members\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityInterest extends Model
{
    protected $table = 'member_opportunity_interests';

    protected $fillable = [
        'member_id',
        'interest_type',
        'opportunity_category',
        'notes',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
