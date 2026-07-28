<?php

namespace Database\Factories;

use App\Domains\Intelligence\Models\MemoryRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemoryRecordFactory extends Factory
{
    protected $model = MemoryRecord::class;

    public function definition(): array
    {
        return [
            'subject_type' => 'organization',
            'subject_id' => 1,
            'memory_type' => 'fact',
            'content' => fake()->sentence(),
            'confidence_score' => 0.75,
            'visibility' => 'organization',
            'metadata' => [],
        ];
    }
}
