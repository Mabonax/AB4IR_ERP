<?php

namespace App\Domains\Facilitators\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Facilitators\Requests\StoreFacilitatorRequest;
use App\Domains\Facilitators\Requests\UpdateFacilitatorRequest;
use App\Domains\Facilitators\Resources\FacilitatorResource;
use App\Domains\Facilitators\Services\FacilitatorService;
use App\Domains\Projects\Services\LmsLearningDeliveryClient;
use App\Domains\Projects\Services\ProjectLearningDeliveryService;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacilitatorController extends Controller
{
    public function __construct(
        protected FacilitatorService $service,
        protected LmsLearningDeliveryClient $lmsLearningClient,
        protected ProjectLearningDeliveryService $projectLearningDelivery,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Facilitator::class);

        return Inertia::render('Facilitators/Index', [
            'facilitators' => FacilitatorResource::collection(
                $this->service->paginateFacilitators()
            ),
            'canManageFacilitators' => (bool) $request->user()?->can('create', Facilitator::class),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Facilitator::class);

        return Inertia::render('Facilitators/Create', [
            'provinces' => $this->provinceOptions(),
        ]);
    }

    public function store(StoreFacilitatorRequest $request)
    {
        $this->authorize('create', Facilitator::class);

        $facilitator = $this->service->create($request->validated());

        return redirect()->route('facilitators.show', $facilitator->id)->with('success', 'Facilitator created');
    }

    public function show(Request $request, int $facilitator)
    {
        $model = $this->service->getById($facilitator);
        $this->authorize('view', $model);

        $resource = new FacilitatorResource($model);

        if ($request->expectsJson()) {
            return response()->json($resource);
        }

        return Inertia::render('Facilitators/Show', [
            'facilitator' => $resource->resolve(),
            'canManageFacilitators' => (bool) $request->user()?->can('update', $model),
            'learningSummary' => $this->lmsLearningClient->facilitatorSummary($model),
        ]);
    }

    public function edit(int $facilitator)
    {
        $model = $this->service->getById($facilitator);
        $this->authorize('update', $model);

        return Inertia::render('Facilitators/Edit', [
            'facilitator' => (new FacilitatorResource($model))->resolve(),
            'provinces' => $this->provinceOptions(),
        ]);
    }

    public function update(UpdateFacilitatorRequest $request, int $facilitator)
    {
        $model = $this->service->getById($facilitator);
        $this->authorize('update', $model);

        $updated = $this->service->update($facilitator, $request->validated());

        return redirect()->route('facilitators.show', $updated->id)->with('success', 'Facilitator updated');
    }

    public function destroy(int $facilitator)
    {
        $model = $this->service->getById($facilitator);
        $this->authorize('delete', $model);

        $this->service->delete($facilitator);

        return redirect()->route('facilitators.index')->with('success', 'Facilitator deleted');
    }

    public function resendLmsInvitation(Request $request, int $facilitator): RedirectResponse
    {
        $model = $this->service->getById($facilitator);
        $this->authorize('update', $model);

        $result = $this->lmsLearningClient->resendFacilitatorInvitation($model);

        if (($result['status'] ?? null) === 'invitation_resent') {
            return redirect()
                ->route('facilitators.show', $facilitator)
                ->with('success', 'LMS invitation resent.')
                ->with('activation_url', $result['activation_url'] ?? null);
        }

        return redirect()
            ->route('facilitators.show', $facilitator)
            ->with('error', $result['reason'] ?? 'LMS invitation could not be resent.');
    }

    public function provisionLmsAccess(Request $request, int $facilitator): RedirectResponse
    {
        $model = $this->service->getById($facilitator);
        $this->authorize('update', $model);

        $project = $model->projectLocations()
            ->with('project.learningMappings')
            ->get()
            ->map(fn ($location) => $location->project)
            ->filter()
            ->first(fn ($project) => $project->learningMappings->where('status', 'active')->isNotEmpty());

        if (! $project) {
            return redirect()
                ->route('facilitators.show', $facilitator)
                ->with('error', 'This facilitator is not assigned to a project with active LMS learning delivery.');
        }

        $result = $this->projectLearningDelivery->provisionFacilitators($project, [$model->id], $request->user());
        $item = $result['items'][0] ?? null;

        if (($result['status'] ?? null) === 'rejected') {
            return redirect()
                ->route('facilitators.show', $facilitator)
                ->with('error', $result['reason'] ?? 'LMS access could not be provisioned.');
        }

        if (($item['status'] ?? null) === 'rejected') {
            return redirect()
                ->route('facilitators.show', $facilitator)
                ->with('error', $item['reason'] ?? 'LMS access could not be provisioned.');
        }

        return redirect()
            ->route('facilitators.show', $facilitator)
            ->with('success', $item['reason'] ?? 'LMS access provisioning requested.')
            ->with('activation_url', $item['activation_url'] ?? null);
    }

    protected function provinceOptions()
    {
        return Provinces::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}
