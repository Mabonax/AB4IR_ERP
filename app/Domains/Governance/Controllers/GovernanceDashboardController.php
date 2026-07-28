<?php

namespace App\Domains\Governance\Controllers;

use App\Domains\Governance\Models\GovernanceStructure;
use App\Domains\Governance\Requests\StoreGovernanceStructureRequest;
use App\Domains\Governance\Requests\UpdateGovernanceStructureRequest;
use App\Domains\Governance\Services\GovernanceStructureService;
use App\Domains\Organisation\Services\OrganisationService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class GovernanceDashboardController extends Controller
{
    public function __construct(
        protected GovernanceStructureService $service,
        protected OrganisationService $organisationService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', GovernanceStructure::class);

        $dashboard = $this->service->dashboard();

        return Inertia::render('Governance/Index', [
            'stats' => $dashboard['stats'],
            'structures' => $dashboard['structures'],
            'organisations' => $this->organisationService->all()
                ->map(fn ($organisation) => [
                    'id' => $organisation->id,
                    'name' => $organisation->name,
                ])->values()->all(),
        ]);
    }

    public function store(StoreGovernanceStructureRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Governance structure added.');
    }

    public function update(UpdateGovernanceStructureRequest $request, int $structure)
    {
        $model = $this->service->findOrFail($structure);
        $this->authorize('update', $model);

        $this->service->update($structure, $request->validated());

        return redirect()->back()->with('success', 'Governance structure updated.');
    }
}
