<?php

namespace Database\Factories;

use App\Domains\Intelligence\Models\PromptTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PromptTemplateFactory extends Factory
{
    protected $model = PromptTemplate::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        $slug = Str::slug($name);

        return [
            'name' => Str::title($name),
            'slug' => $slug,
            'description' => fake()->sentence(),
            'category' => 'operations',
            'version' => 1,
            'status' => 'active',
            'system_prompt' => 'System {{subject}}',
            'developer_prompt' => 'Developer {{subject}}',
            'user_prompt_template' => 'User {{subject}}',
            'variables_schema' => ['properties' => ['subject' => ['type' => 'string']]],
            'output_schema' => ['type' => 'object'],
            'owner_user_id' => User::factory(),
            'is_default' => true,
            'metadata' => [],
        ];
    }
}
