<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\ModelRoutingRule;
use App\Domains\Intelligence\Requests\StoreModelRoutingRuleRequest;
use App\Domains\Intelligence\Requests\UpdateModelRoutingRuleRequest;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ModelRoutingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ModelRoutingRule::class);

        return Inertia::render('Intelligence/Routing', [
            'rules' => ModelRoutingRule::query()->orderBy('priority')->get()->map(fn (ModelRoutingRule $rule) => [
                'id' => $rule->id,
                'provider' => $rule->provider,
                'model' => $rule->model,
                'capability' => $rule->capability,
                'priority' => $rule->priority,
                'max_context_tokens' => $rule->max_context_tokens,
                'cost_tier' => $rule->cost_tier,
                'enabled' => $rule->enabled,
                'fallback_provider' => $rule->fallback_provider,
                'fallback_model' => $rule->fallback_model,
            ])->values(),
        ]);
    }

    public function store(StoreModelRoutingRuleRequest $request)
    {
        $this->authorize('create', ModelRoutingRule::class);

        ModelRoutingRule::query()->create($request->validated());

        return redirect()->back()->with('success', 'Routing rule created.');
    }

    public function update(UpdateModelRoutingRuleRequest $request, ModelRoutingRule $rule)
    {
        $this->authorize('update', $rule);

        $rule->update($request->validated());

        return redirect()->back()->with('success', 'Routing rule updated.');
    }
}
