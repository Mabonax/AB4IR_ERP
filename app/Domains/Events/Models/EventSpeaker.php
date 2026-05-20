<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSpeaker extends Model
{
    use HasFactory;

    protected $table = 'event_speakers';

    protected $fillable = [
        'event_id',
        'name',
        'title',
        'organization_name',
        'topic',
        'bio',
        'email',
        'phone',
        'sort_order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
