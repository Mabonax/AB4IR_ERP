<?php

namespace App\Domains\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgrammePartnership extends Model
{
    protected $fillable = [
        'organisation',
        'contact_person',
        'contact_email',
        'contact_phone',
        'partnership_type',
        'status',
    ];

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            Program::class,
            'programme_partnership_program',
            'programme_partnership_id',
            'program_id'
        )->withTimestamps();
    }
}
