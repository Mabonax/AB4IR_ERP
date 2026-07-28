<?php

namespace App\Domains\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgrammeOutcome extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'target',
        'actual',
        'reporting_period',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
