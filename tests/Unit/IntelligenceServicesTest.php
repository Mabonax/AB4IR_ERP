<?php

use App\Domains\Intelligence\Enums\ModelCapability;
use App\Domains\Intelligence\Handlers\CalculatorToolHandler;
use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Models\MemoryRecord;
use App\Domains\Intelligence\Models\ModelRoutingRule;
use App\Domains\Intelligence\Models\PromptTemplate;
use App\Domains\Intelligence\Services\MemoryRetriever;
use App\Domains\Intelligence\Services\ModelRouter;
use App\Domains\Intelligence\Services\PromptTemplateRenderer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prompt template renderer replaces variables', function () {
    $template = PromptTemplate::factory()->create([
        'system_prompt' => 'System {{message}}',
        'developer_prompt' => 'Developer {{message}}',
        'user_prompt_template' => 'User {{message}}',
        'variables_schema' => ['properties' => ['message' => ['type' => 'string']]],
    ]);

    $rendered = app(PromptTemplateRenderer::class)->render($template, ['message' => 'hello']);

    expect($rendered['system'])->toBe('System hello')
        ->and($rendered['developer'])->toBe('Developer hello')
        ->and($rendered['user'])->toBe('User hello');
});

test('memory retriever filters by confidence visibility and expiry', function () {
    $agent = Agent::factory()->create(['visibility' => 'organization']);

    MemoryRecord::factory()->create([
        'subject_type' => 'organization',
        'subject_id' => 5,
        'visibility' => 'organization',
        'confidence_score' => 0.8,
        'content' => 'Trusted memory',
    ]);

    MemoryRecord::factory()->create([
        'subject_type' => 'organization',
        'subject_id' => 5,
        'visibility' => 'global',
        'confidence_score' => 0.4,
        'content' => 'Low confidence memory',
    ]);

    $records = app(MemoryRetriever::class)->retrieve('organization', 5, $agent);

    expect($records)->toHaveCount(1)
        ->and($records->first()->content)->toBe('Trusted memory');
});

test('tool runtime calculator executes safely', function () {
    $result = app(CalculatorToolHandler::class)->handle([
        'left' => 10,
        'right' => 5,
        'operator' => '/',
    ], User::factory()->create());

    expect($result->success)->toBeTrue()
        ->and($result->output['result'])->toBe(2.0);
});

test('model router falls back when no rule exists', function () {
    $route = app(ModelRouter::class)->route(ModelCapability::Vision);

    expect($route['provider'])->toBe(config('intelligence.model_routing.fallback_provider'))
        ->and($route['model'])->toBe(config('intelligence.model_routing.fallback_model'));
});

test('model router respects explicit rule priority', function () {
    ModelRoutingRule::factory()->create([
        'capability' => 'coding',
        'provider' => 'stub',
        'model' => 'stub-code-v1',
        'priority' => 1,
    ]);

    $route = app(ModelRouter::class)->route(ModelCapability::Coding);

    expect($route['model'])->toBe('stub-code-v1');
});
