<?php

namespace App\Domains\Intelligence\Models;

use Database\Factories\AiToolFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTool extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return AiToolFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'handler_class',
        'input_schema',
        'output_schema',
        'status',
        'requires_approval',
        'permission_key',
        'timeout_seconds',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'input_schema' => 'array',
            'output_schema' => 'array',
            'requires_approval' => 'boolean',
            'timeout_seconds' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ToolExecutionLog::class, 'ai_tool_id');
    }
}
