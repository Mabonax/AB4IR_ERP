<?php

namespace Database\Factories;

use App\Domains\Intelligence\Models\AiTool;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiToolFactory extends Factory
{
    protected $model = AiTool::class;

    public function definition(): array
    {
        return [
            'name' => 'Current Datetime',
            'slug' => 'current_datetime',
            'description' => 'Return the current platform datetime.',
            'category' => 'system',
            'handler_class' => \App\Domains\Intelligence\Handlers\CurrentDatetimeToolHandler::class,
            'input_schema' => ['type' => 'object'],
            'output_schema' => ['type' => 'object'],
            'status' => 'active',
            'requires_approval' => false,
            'permission_key' => 'domain.intelligence.manage',
            'timeout_seconds' => 10,
            'metadata' => [],
        ];
    }
}
