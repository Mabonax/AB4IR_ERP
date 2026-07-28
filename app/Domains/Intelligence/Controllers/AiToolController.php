<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\AiTool;
use App\Domains\Intelligence\Requests\StoreAiToolRequest;
use App\Domains\Intelligence\Requests\UpdateAiToolRequest;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AiToolController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiTool::class);

        return Inertia::render('Intelligence/Tools', [
            'tools' => AiTool::query()->orderBy('name')->get()->map(fn (AiTool $tool) => [
                'id' => $tool->id,
                'name' => $tool->name,
                'slug' => $tool->slug,
                'description' => $tool->description,
                'category' => $tool->category,
                'handler_class' => $tool->handler_class,
                'status' => $tool->status,
                'requires_approval' => $tool->requires_approval,
                'permission_key' => $tool->permission_key,
                'timeout_seconds' => $tool->timeout_seconds,
                'input_schema' => $tool->input_schema ?? [],
                'output_schema' => $tool->output_schema ?? [],
            ])->values(),
        ]);
    }

    public function store(StoreAiToolRequest $request)
    {
        $this->authorize('create', AiTool::class);

        AiTool::query()->create($request->validated());

        return redirect()->back()->with('success', 'AI tool created.');
    }

    public function update(UpdateAiToolRequest $request, AiTool $tool)
    {
        $this->authorize('update', $tool);

        $tool->update($request->validated());

        return redirect()->back()->with('success', 'AI tool updated.');
    }
}
