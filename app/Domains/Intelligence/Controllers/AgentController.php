<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Requests\StoreAgentRequest;
use App\Domains\Intelligence\Requests\UpdateAgentRequest;
use App\Domains\Intelligence\Services\AgentManager;
use App\Domains\Intelligence\Services\ToolRegistry;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function __construct(
        protected AgentManager $manager,
        protected ToolRegistry $toolRegistry
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Agent::class);

        return Inertia::render('Intelligence/Agents', [
            'agents' => $this->manager->all()->map(fn (Agent $agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
                'description' => $agent->description,
                'status' => $agent->status,
                'purpose' => $agent->purpose,
                'default_provider' => $agent->default_provider,
                'default_model' => $agent->default_model,
                'temperature' => $agent->temperature,
                'max_tokens' => $agent->max_tokens,
                'allowed_tools' => $agent->allowed_tools ?? [],
                'allowed_knowledge_sources' => $agent->allowed_knowledge_sources ?? [],
                'memory_enabled' => $agent->memory_enabled,
                'conversation_limit' => $agent->conversation_limit,
                'visibility' => $agent->visibility,
                'owner_name' => $agent->owner?->name,
                'metadata' => $agent->metadata ?? [],
            ])->values(),
            'toolOptions' => $this->toolRegistry->all()->map(fn ($tool) => [
                'label' => $tool->name,
                'value' => $tool->slug,
            ])->values(),
        ]);
    }

    public function store(StoreAgentRequest $request)
    {
        $this->authorize('create', Agent::class);

        $this->manager->create($request->validated(), $request->user());

        return redirect()->back()->with('success', 'Agent created.');
    }

    public function update(UpdateAgentRequest $request, Agent $agent)
    {
        $this->authorize('update', $agent);

        $this->manager->update($agent, $request->validated());

        return redirect()->back()->with('success', 'Agent updated.');
    }
}
