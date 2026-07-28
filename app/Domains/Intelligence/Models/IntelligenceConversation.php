<?php

namespace App\Domains\Intelligence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntelligenceConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'user_id',
        'subject_type',
        'subject_id',
        'title',
        'status',
        'last_message_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'agent_id' => 'integer',
            'user_id' => 'integer',
            'subject_id' => 'integer',
            'last_message_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(IntelligenceMessage::class, 'conversation_id');
    }
}
