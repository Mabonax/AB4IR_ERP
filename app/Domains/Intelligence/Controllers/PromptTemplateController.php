<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\PromptTemplate;
use App\Domains\Intelligence\Requests\StorePromptTemplateRequest;
use App\Domains\Intelligence\Requests\UpdatePromptTemplateRequest;
use App\Domains\Intelligence\Services\PromptTemplateRepository;
use App\Domains\Intelligence\Services\PromptVersioningService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PromptTemplateController extends Controller
{
    public function __construct(
        protected PromptTemplateRepository $repository,
        protected PromptVersioningService $versioningService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PromptTemplate::class);

        return Inertia::render('Intelligence/Prompts', [
            'prompts' => $this->repository->all()->map(fn (PromptTemplate $prompt) => [
                'id' => $prompt->id,
                'name' => $prompt->name,
                'slug' => $prompt->slug,
                'description' => $prompt->description,
                'category' => $prompt->category,
                'version' => $prompt->version,
                'status' => $prompt->status,
                'system_prompt' => $prompt->system_prompt,
                'developer_prompt' => $prompt->developer_prompt,
                'user_prompt_template' => $prompt->user_prompt_template,
                'variables_schema' => $prompt->variables_schema ?? [],
                'output_schema' => $prompt->output_schema ?? [],
                'owner_name' => $prompt->owner?->name,
                'is_default' => $prompt->is_default,
            ])->values(),
        ]);
    }

    public function store(StorePromptTemplateRequest $request)
    {
        $this->authorize('create', PromptTemplate::class);

        $data = $request->validated();
        $data['version'] = $this->versioningService->nextVersionForSlug($data['slug']);
        $data['owner_user_id'] = $request->user()->id;

        $prompt = PromptTemplate::query()->create($data);

        if ($prompt->is_default || $prompt->status === 'active') {
            $this->versioningService->activate($prompt);
        }

        return redirect()->back()->with('success', 'Prompt template created.');
    }

    public function update(UpdatePromptTemplateRequest $request, PromptTemplate $promptTemplate)
    {
        $this->authorize('update', $promptTemplate);

        $promptTemplate->update($request->validated());

        if ($promptTemplate->is_default || $promptTemplate->status === 'active') {
            $this->versioningService->activate($promptTemplate);
        }

        return redirect()->back()->with('success', 'Prompt template updated.');
    }

    public function activate(PromptTemplate $promptTemplate)
    {
        $this->authorize('update', $promptTemplate);

        $this->versioningService->activate($promptTemplate);

        return redirect()->back()->with('success', 'Prompt template activated.');
    }
}
