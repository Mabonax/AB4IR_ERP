<?php

namespace App\Domains\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventClosureReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'attendance_summary',
        'registration_summary',
        'budget_summary',
        'outcomes_achieved',
        'lessons_learned',
        'risks_encountered',
        'recommendations',
        'closure_reason',
        'closed_by_user_id',
        'closed_at',
    ];

    protected $casts = [
        'attendance_summary' => 'array',
        'registration_summary' => 'array',
        'closed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function assets()
    {
        return $this->hasMany(EventClosureAsset::class)->latest();
    }
}
