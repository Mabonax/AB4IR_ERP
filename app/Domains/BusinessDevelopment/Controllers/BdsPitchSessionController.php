<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Domains\BusinessDevelopment\Models\BdsPitchSessionProspect;
use App\Domains\BusinessDevelopment\Requests\ApproveBdsPitchSessionProspectRequest;
use App\Domains\BusinessDevelopment\Requests\StoreBdsPitchSessionRequest;
use App\Domains\BusinessDevelopment\Resources\BdsPitchSessionResource;
use App\Domains\BusinessDevelopment\Services\BdsPitchSessionService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BdsPitchSessionController extends Controller
{
    public function __construct(
        protected BdsPitchSessionService $service
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', BdsPitchSession::class);

        $perPage = (int) $request->integer('per_page', 15);

        return Inertia::render('BusinessDevelopment/PitchSessions/Index', [
            'sessions' => BdsPitchSessionResource::collection(
                $this->service->paginate($perPage, $request->user())
            ),
            'panelists' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'prospects' => BdsApplication::query()
                ->where('assessment_status', 'accepted')
                ->whereNull('adjudication_result')
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'full_name', 'pitch_scheduled_at'])
                ->map(fn (BdsApplication $application) => [
                    'id' => $application->id,
                    'company_name' => $application->company_name,
                    'full_name' => $application->full_name,
                    'pitch_scheduled_at' => $application->pitch_scheduled_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function show(BdsPitchSession $pitch_session)
    {
        $this->authorize('view', $pitch_session);

        return Inertia::render('BusinessDevelopment/PitchSessions/Show', [
            'session' => new BdsPitchSessionResource(
                $this->service->getById($pitch_session->id)
            ),
            'can' => [
                'start' => auth()->user()?->can('start', $pitch_session) ?? false,
                'consolidate' => auth()->user()?->can('consolidate', $pitch_session) ?? false,
                'approve' => auth()->user()?->can('approve', $pitch_session) ?? false,
            ],
        ]);
    }

    public function store(StoreBdsPitchSessionRequest $request)
    {
        $this->authorize('create', BdsPitchSession::class);

        $session = $this->service->createSession($request->validated(), $request->user());

        return redirect()
            ->route('business-development.pitch-sessions.show', $session)
            ->with('success', 'Pitch session scheduled.');
    }

    public function start(Request $request, BdsPitchSession $pitch_session)
    {
        $this->authorize('start', $pitch_session);

        $this->service->startSession($pitch_session, $request->user());

        return redirect()
            ->route('business-development.pitch-sessions.show', $pitch_session)
            ->with('success', 'Pitch session started.');
    }

    public function consolidate(Request $request, BdsPitchSession $pitch_session, BdsPitchSessionProspect $prospect)
    {
        abort_unless($prospect->pitch_session_id === $pitch_session->id, 404);
        $this->authorize('consolidate', $pitch_session);

        $this->service->consolidateProspect($prospect, $request->user());

        return redirect()
            ->route('business-development.pitch-sessions.show', $pitch_session)
            ->with('success', 'Prospect panel scores consolidated.');
    }

    public function approve(
        ApproveBdsPitchSessionProspectRequest $request,
        BdsPitchSession $pitch_session,
        BdsPitchSessionProspect $prospect
    ) {
        abort_unless($prospect->pitch_session_id === $pitch_session->id, 404);
        $this->authorize('approve', $pitch_session);

        $this->service->approveProspect(
            $prospect,
            $request->user(),
            $request->string('manager_decision')->toString(),
            $request->input('manager_notes')
        );

        return redirect()
            ->route('business-development.pitch-sessions.show', $pitch_session)
            ->with('success', 'Manager decision saved.');
    }
}
