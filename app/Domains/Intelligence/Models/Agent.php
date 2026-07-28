<?php

namespace App\Domains\Intelligence\Models;

use App\Models\User;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return AgentFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'purpose',
        'system_instructions',
        'default_provider',
        'default_model',
        'temperature',
        'max_tokens',
        'allowed_tools',
        'allowed_knowledge_sources',
        'memory_enabled',
        'conversation_limit',
        'visibility',
        'owner_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'max_tokens' => 'integer',
            'allowed_tools' => 'array',
            'allowed_knowledge_sources' => 'array',
            'memory_enabled' => 'boolean',
            'conversation_limit' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(IntelligenceConversation::class);
    }
}
