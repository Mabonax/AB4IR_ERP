<?php

namespace App\Domains\Meetings\Models;

use App\Domains\Committees\Models\Committee;
use App\Domains\Organisation\Models\Organisation;
use App\Domains\Resolutions\Models\Resolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $fillable = [
        'organisation_id',
        'committee_id',
        'meeting_number',
        'title',
        'meeting_date',
        'location',
        'agenda',
        'minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class);
    }
}
