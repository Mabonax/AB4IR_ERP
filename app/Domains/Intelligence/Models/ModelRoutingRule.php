<?php

namespace App\Domains\Intelligence\Models;

use Database\Factories\ModelRoutingRuleFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelRoutingRule extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ModelRoutingRuleFactory::new();
    }

    protected $fillable = [
        'provider',
        'model',
        'capability',
        'priority',
        'max_context_tokens',
        'cost_tier',
        'enabled',
        'fallback_provider',
        'fallback_model',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'max_context_tokens' => 'integer',
            'enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
