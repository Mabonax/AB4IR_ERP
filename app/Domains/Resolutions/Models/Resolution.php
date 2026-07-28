<?php

namespace App\Domains\Resolutions\Models;

use App\Domains\Meetings\Models\Meeting;
use App\Domains\Organisation\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resolution extends Model
{
    protected $fillable = [
        'organisation_id',
        'meeting_id',
        'resolution_number',
        'title',
        'description',
        'owner_id',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
