<?php

namespace App\Domains\Intelligence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolExecutionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_tool_id',
        'agent_id',
        'conversation_id',
        'user_id',
        'status',
        'input_payload',
        'output_payload',
        'error_message',
        'approved',
        'executed_at',
        'duration_ms',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'ai_tool_id' => 'integer',
            'agent_id' => 'integer',
            'conversation_id' => 'integer',
            'user_id' => 'integer',
            'input_payload' => 'array',
            'output_payload' => 'array',
            'approved' => 'boolean',
            'executed_at' => 'datetime',
            'duration_ms' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(AiTool::class, 'ai_tool_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
