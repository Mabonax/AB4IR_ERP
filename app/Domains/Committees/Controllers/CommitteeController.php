<?php

namespace App\Domains\Committees\Controllers;

use App\Domains\Committees\Models\Committee;
use App\Domains\Committees\Requests\StoreCommitteeRequest;
use App\Domains\Committees\Requests\UpdateCommitteeRequest;
use App\Domains\Committees\Services\CommitteeService;
use App\Domains\Organisation\Services\OrganisationService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class CommitteeController extends Controller
{
    public function __construct(
        protected CommitteeService $service,
        protected OrganisationService $organisationService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Committee::class);

        $dashboard = $this->service->dashboard();

        return Inertia::render('Committees/Index', [
            'stats' => $dashboard['stats'],
            'committees' => $dashboard['committees'],
            'organisations' => $this->organisationService->all()
                ->map(fn ($organisation) => ['id' => $organisation->id, 'name' => $organisation->name])
                ->values()
                ->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values()->all(),
        ]);
    }

    public function store(StoreCommitteeRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Committee added.');
    }

    public function update(UpdateCommitteeRequest $request, int $committee)
    {
        $model = $this->service->findOrFail($committee);
        $this->authorize('update', $model);

        $this->service->update($committee, $request->validated());

        return redirect()->back()->with('success', 'Committee updated.');
    }
}
