<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventWorkstream extends Model
{
    use HasFactory;

    protected $table = 'event_workstreams';

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'sort_order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tasks()
    {
        return $this->hasMany(EventTask::class)->orderBy('phase')->orderBy('sort_order')->orderBy('due_date');
    }
}
