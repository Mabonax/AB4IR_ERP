<?php

namespace App\Domains\Organisation\Controllers;

use App\Domains\Organisation\Models\Organisation;
use App\Domains\Organisation\Requests\StoreOrganisationRequest;
use App\Domains\Organisation\Requests\UpdateOrganisationRequest;
use App\Domains\Organisation\Services\OrganisationService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class OrganisationRegistryController extends Controller
{
    public function __construct(
        protected OrganisationService $service
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Organisation::class);

        $registry = $this->service->registry();

        return Inertia::render('Organization/Registry', [
            'stats' => $registry['stats'],
            'organisations' => $registry['organisations'],
        ]);
    }

    public function store(StoreOrganisationRequest $request)
    {
        $this->service->register($request->validated());

        return redirect()->back()->with('success', 'Organisation added to registry.');
    }

    public function update(UpdateOrganisationRequest $request, int $organisation)
    {
        $model = $this->service->findOrFail($organisation);
        $this->authorize('update', $model);

        $this->service->update($organisation, $request->validated());

        return redirect()->back()->with('success', 'Organisation registry entry updated.');
    }
}
