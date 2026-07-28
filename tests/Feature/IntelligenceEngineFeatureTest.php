<?php

use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Models\IntelligenceConversation;
use App\Domains\Intelligence\Models\ToolExecutionLog;
use App\Models\User;
use Database\Seeders\IntelligenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeIntelligenceManager(): User
{
    $user = User::factory()->create();
    grantDomainAccess($user, 'intelligence');

    return $user->refresh();
}

test('intelligence managers can view the workspace and agent registry', function () {
    $user = makeIntelligenceManager();
    $this->seed(IntelligenceSeeder::class);

    $this->actingAs($user)
        ->get(route('intelligence.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Intelligence/Dashboard')
            ->where('summary.agents', 1)
        );

    $this->actingAs($user)
        ->get(route('intelligence.agents.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Intelligence/Agents')
            ->where('agents.0.slug', config('intelligence.default_agent_slug'))
        );
});

test('intelligence managers can create agents', function () {
    $user = makeIntelligenceManager();

    $this->actingAs($user)->post(route('intelligence.agents.store'), [
        'name' => 'Portfolio Monitor',
        'slug' => 'portfolio-monitor',
        'description' => 'Tracks programme delivery.',
        'status' => 'active',
        'purpose' => 'Monitor delivery and summarize movement.',
        'system_instructions' => 'Remain inside POA ERP.',
        'default_provider' => 'stub',
        'default_model' => 'stub-chat-v1',
        'temperature' => 0.2,
        'max_tokens' => 1024,
        'allowed_tools' => ['current_datetime'],
        'allowed_knowledge_sources' => ['projects'],
        'memory_enabled' => true,
        'conversation_limit' => 20,
        'visibility' => 'organization',
        'metadata' => [],
    ])->assertRedirect()->assertSessionHas('success', 'Agent created.');

    $this->assertDatabaseHas('agents', [
        'slug' => 'portfolio-monitor',
        'status' => 'active',
    ]);
});

test('conversation execution through an agent stores usage and tool logs', function () {
    $user = makeIntelligenceManager();
    $this->seed(IntelligenceSeeder::class);

    $agent = Agent::query()->where('slug', config('intelligence.default_agent_slug'))->firstOrFail();

    $this->actingAs($user)->post(route('intelligence.conversations.store'), [
        'agent_slug' => $agent->slug,
        'subject_type' => 'organization',
        'subject_id' => 1,
        'message' => 'Summarize the current platform state.',
    ])->assertRedirect()->assertSessionHas('success', 'Agent execution completed.');

    $conversation = IntelligenceConversation::query()->latest('id')->firstOrFail();

    expect($conversation->messages()->count())->toBe(2)
        ->and($conversation->messages()->where('role', 'assistant')->first()?->provider)->toBe('stub')
        ->and(ToolExecutionLog::query()->count())->toBeGreaterThan(0);
});

test('intelligence routes enforce permissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('intelligence.index'))->assertForbidden();
    $this->actingAs($user)->get(route('intelligence.agents.index'))->assertForbidden();
});
