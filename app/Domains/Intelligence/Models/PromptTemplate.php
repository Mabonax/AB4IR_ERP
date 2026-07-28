<?php

namespace App\Domains\Intelligence\Models;

use App\Models\User;
use Database\Factories\PromptTemplateFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptTemplate extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return PromptTemplateFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'version',
        'status',
        'system_prompt',
        'developer_prompt',
        'user_prompt_template',
        'variables_schema',
        'output_schema',
        'owner_user_id',
        'is_default',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'variables_schema' => 'array',
            'output_schema' => 'array',
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
