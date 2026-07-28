<?php

namespace App\Domains\Intelligence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntelligenceMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(IntelligenceConversation::class, 'conversation_id');
    }
}
