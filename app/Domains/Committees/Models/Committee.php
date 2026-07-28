<?php

namespace App\Domains\Committees\Models;

use App\Domains\Meetings\Models\Meeting;
use App\Domains\Organisation\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Committee extends Model
{
    protected $fillable = [
        'organisation_id',
        'name',
        'description',
        'chairperson_id',
        'secretary_id',
        'status',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function chairperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}
