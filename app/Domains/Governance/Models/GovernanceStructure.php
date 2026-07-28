<?php

namespace App\Domains\Governance\Models;

use App\Domains\Organisation\Models\Organisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceStructure extends Model
{
    protected $fillable = [
        'organisation_id',
        'name',
        'description',
        'status',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
