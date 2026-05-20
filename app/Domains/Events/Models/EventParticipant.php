<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventParticipant extends Model
{
    use HasFactory;

    protected $table = 'event_participants';

    protected $fillable = [
        'event_id',
        'category',
        'name',
        'surname',
        'title',
        'organization_name',
        'topic',
        'bio',
        'email',
        'phone',
        'role',
        'attendance_type',
        'attendance_status',
        'checked_in_at',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
