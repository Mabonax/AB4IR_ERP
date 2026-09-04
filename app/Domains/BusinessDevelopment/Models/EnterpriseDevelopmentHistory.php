<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDevelopmentHistory extends Model
{
    protected $table = 'enterprise_development_history';

    protected $fillable = [
        'bds_incubatee_id',
        'event_type',
        'title',
        'details',
        'metadata',
        'actor_id',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
