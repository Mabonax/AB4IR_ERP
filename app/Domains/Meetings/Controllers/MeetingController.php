<?php

namespace App\Domains\Meetings\Controllers;

use App\Domains\Committees\Services\CommitteeService;
use App\Domains\Meetings\Models\Meeting;
use App\Domains\Meetings\Requests\StoreMeetingRequest;
use App\Domains\Meetings\Requests\UpdateMeetingRequest;
use App\Domains\Meetings\Services\MeetingService;
use App\Domains\Organisation\Services\OrganisationService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function __construct(
        protected MeetingService $service,
        protected OrganisationService $organisationService,
        protected CommitteeService $committeeService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Meeting::class);

        $dashboard = $this->service->dashboard();

        return Inertia::render('Meetings/Index', [
            'stats' => $dashboard['stats'],
            'meetings' => $dashboard['meetings'],
            'organisations' => $this->organisationService->all()
                ->map(fn ($organisation) => ['id' => $organisation->id, 'name' => $organisation->name])
                ->values()->all(),
            'committees' => collect($this->committeeService->dashboard()['committees'])
                ->map(fn (array $committee) => ['id' => $committee['id'], 'name' => $committee['name']])
                ->values()->all(),
        ]);
    }

    public function store(StoreMeetingRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Meeting added.');
    }

    public function update(UpdateMeetingRequest $request, int $meeting)
    {
        $model = $this->service->findOrFail($meeting);
        $this->authorize('update', $model);

        $this->service->update($meeting, $request->validated());

        return redirect()->back()->with('success', 'Meeting updated.');
    }
}
