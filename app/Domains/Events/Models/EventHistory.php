<?php

namespace App\Domains\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventHistory extends Model
{
    use HasFactory;

    protected $table = 'event_history';

    protected $fillable = [
        'event_id',
        'actor_user_id',
        'action',
        'from_status',
        'to_status',
        'summary',
        'reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
