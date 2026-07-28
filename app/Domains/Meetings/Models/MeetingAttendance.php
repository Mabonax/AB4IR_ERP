<?php

namespace App\Domains\Meetings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    protected $table = 'meeting_attendance';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'attendance_status',
        'remarks',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
