<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    use HasFactory;

    protected $table = 'event_attendees';

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'organization_name',
        'role',
        'attendance_status',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
