<?php

namespace Database\Factories;

use App\Domains\Intelligence\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Agent';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'status' => 'active',
            'purpose' => fake()->sentence(),
            'system_instructions' => 'Operate safely inside the Programme of Action ERP.',
            'default_provider' => 'stub',
            'default_model' => 'stub-chat-v1',
            'temperature' => 0.2,
            'max_tokens' => 1024,
            'allowed_tools' => ['current_datetime', 'platform_status'],
            'allowed_knowledge_sources' => ['organization', 'projects'],
            'memory_enabled' => true,
            'conversation_limit' => 30,
            'visibility' => 'organization',
            'owner_user_id' => User::factory(),
            'metadata' => [],
        ];
    }
}
