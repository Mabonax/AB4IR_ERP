<?php

namespace App\Domains\Intelligence\Models;

use App\Models\User;
use Database\Factories\MemoryRecordFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryRecord extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return MemoryRecordFactory::new();
    }

    protected $fillable = [
        'subject_type',
        'subject_id',
        'memory_type',
        'content',
        'confidence_score',
        'source_conversation_id',
        'source_message_id',
        'visibility',
        'expires_at',
        'reviewed_at',
        'approved_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'confidence_score' => 'float',
            'source_conversation_id' => 'integer',
            'source_message_id' => 'integer',
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_by' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
