<?php

namespace App\Domains\Resolutions\Controllers;

use App\Domains\Meetings\Services\MeetingService;
use App\Domains\Organisation\Services\OrganisationService;
use App\Domains\Resolutions\Models\Resolution;
use App\Domains\Resolutions\Requests\StoreResolutionRequest;
use App\Domains\Resolutions\Requests\UpdateResolutionRequest;
use App\Domains\Resolutions\Services\ResolutionService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class ResolutionController extends Controller
{
    public function __construct(
        protected ResolutionService $service,
        protected OrganisationService $organisationService,
        protected MeetingService $meetingService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Resolution::class);

        $dashboard = $this->service->dashboard();

        return Inertia::render('Resolutions/Index', [
            'stats' => $dashboard['stats'],
            'resolutions' => $dashboard['resolutions'],
            'organisations' => $this->organisationService->all()
                ->map(fn ($organisation) => ['id' => $organisation->id, 'name' => $organisation->name])
                ->values()->all(),
            'meetings' => collect($this->meetingService->dashboard()['meetings'])
                ->map(fn (array $meeting) => ['id' => $meeting['id'], 'name' => $meeting['title']])
                ->values()->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name'])->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values()->all(),
        ]);
    }

    public function store(StoreResolutionRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Resolution added.');
    }

    public function update(UpdateResolutionRequest $request, int $resolution)
    {
        $model = $this->service->findOrFail($resolution);
        $this->authorize('update', $model);

        $this->service->update($resolution, $request->validated());

        return redirect()->back()->with('success', 'Resolution updated.');
    }
}
