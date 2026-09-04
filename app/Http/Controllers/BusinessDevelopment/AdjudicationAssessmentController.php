<?php

namespace App\Http\Controllers\BusinessDevelopment;

use App\Domains\BusinessDevelopment\Adjudication\Actions\CreateAdjudicationAssessmentAction;
use App\Domains\BusinessDevelopment\Adjudication\Actions\SubmitAdjudicationAssessmentAction;
use App\Domains\BusinessDevelopment\Adjudication\Actions\UnlockAdjudicationAssessmentAction;
use App\Domains\BusinessDevelopment\Adjudication\Actions\UpdateAdjudicationAssessmentAction;
use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationSection;
use App\Domains\BusinessDevelopment\Adjudication\Repositories\AdjudicationAssessmentRepositoryInterface;
use App\Domains\BusinessDevelopment\Adjudication\Resources\AdjudicationAssessmentListResource;
use App\Domains\BusinessDevelopment\Adjudication\Resources\AdjudicationAssessmentResource;
use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Domains\BusinessDevelopment\Models\BdsPitchSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessDevelopment\StoreAdjudicationAssessmentRequest;
use App\Http\Requests\BusinessDevelopment\UpdateAdjudicationAssessmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdjudicationAssessmentController extends Controller
{
    public function __construct(
        protected AdjudicationAssessmentRepositoryInterface $repository,
        protected CreateAdjudicationAssessmentAction $createAction,
        protected UpdateAdjudicationAssessmentAction $updateAction,
        protected SubmitAdjudicationAssessmentAction $submitAction,
        protected UnlockAdjudicationAssessmentAction $unlockAction,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AdjudicationAssessment::class);

        $perPage = (int) $request->integer('per_page', 15);
        $assessments = $this->repository->paginateForUser($request->user(), $perPage);

        return Inertia::render('BusinessDevelopment/Adjudications/Index', [
            'assessments' => AdjudicationAssessmentListResource::collection($assessments),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $this->authorize('create', AdjudicationAssessment::class);

        $initialSmmeId = $request->integer('smme_id') ?: null;
        $initialPitchSessionId = $request->integer('pitch_session_id') ?: null;

        if ($initialSmmeId && $initialPitchSessionId) {
            $existingAssessment = AdjudicationAssessment::query()
                ->where('smme_id', $initialSmmeId)
                ->where('pitch_session_id', $initialPitchSessionId)
                ->where('judge_id', (int) $request->user()->id)
                ->latest('id')
                ->first();

            if ($existingAssessment) {
                if ($existingAssessment->status === 'draft') {
                    return redirect()
                        ->route('business-development.adjudications.edit', $existingAssessment)
                        ->with('info', 'Resumed your existing draft scorecard for this prospect.');
                }

                return redirect()
                    ->route('business-development.adjudications.show', $existingAssessment)
                    ->with('warning', 'Your scorecard for this prospect has already been submitted.');
            }
        }

        return Inertia::render('BusinessDevelopment/Adjudications/Create', [
            'sections' => $this->sections(),
            'smmes' => $this->smmes(),
            'initial_smme_id' => $initialSmmeId,
            'initial_pitch_session_id' => $initialPitchSessionId,
            'pitch_sessions' => BdsPitchSession::query()
                ->whereIn('status', ['scheduled', 'in_progress', 'consolidated'])
                ->whereHas('panelists', fn ($panelists) => $panelists->where('user_id', (int) $request->user()->id))
                ->orderBy('scheduled_for')
                ->get(['id', 'title', 'scheduled_for'])
                ->map(fn (BdsPitchSession $session) => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'scheduled_for' => $session->scheduled_for?->toDateTimeString(),
                ]),
        ]);
    }

    public function store(StoreAdjudicationAssessmentRequest $request): RedirectResponse
    {
        $assessment = $this->createAction->execute($request->validated(), $request->user());

        return redirect()
            ->route('business-development.adjudications.show', $assessment)
            ->with('success', 'Assessment draft saved.');
    }

    public function show(AdjudicationAssessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        return Inertia::render('BusinessDevelopment/Adjudications/Show', [
            'assessment' => new AdjudicationAssessmentResource($this->loadAssessment($assessment)),
            'can' => $this->capabilities($assessment),
        ]);
    }

    public function edit(AdjudicationAssessment $assessment): Response
    {
        $this->authorize('view', $assessment);

        return Inertia::render('BusinessDevelopment/Adjudications/Edit', [
            'assessment' => new AdjudicationAssessmentResource($this->loadAssessment($assessment)),
            'sections' => $this->sections(),
            'smmes' => $this->smmes(),
            'can' => $this->capabilities($assessment),
        ]);
    }

    public function update(UpdateAdjudicationAssessmentRequest $request, AdjudicationAssessment $assessment): RedirectResponse
    {
        $assessment = $this->updateAction->execute($assessment, $request->validated(), $request->user());

        return redirect()
            ->route('business-development.adjudications.edit', $assessment)
            ->with('success', 'Assessment draft updated.');
    }

    public function destroy(AdjudicationAssessment $assessment): RedirectResponse
    {
        $this->authorize('delete', $assessment);
        $this->repository->delete($assessment);

        return redirect()
            ->route('business-development.adjudications.index')
            ->with('success', 'Assessment deleted.');
    }

    public function submit(Request $request, AdjudicationAssessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'result' => ['required', 'in:incubated,rejected'],
        ]);

        $this->submitAction->execute($assessment, $request->user(), $validated['result']);

        return redirect()
            ->route('business-development.adjudications.show', $assessment)
            ->with('success', sprintf('Assessment submitted and outcome marked as %s.', $validated['result']));
    }

    public function unlock(Request $request, AdjudicationAssessment $assessment): RedirectResponse
    {
        $this->unlockAction->execute($assessment, $request->user());

        return redirect()
            ->route('business-development.adjudications.edit', $assessment)
            ->with('success', 'Assessment unlocked.');
    }

    protected function loadAssessment(AdjudicationAssessment $assessment): AdjudicationAssessment
    {
        return $assessment->load([
            'judge:id,name',
            'smme:id,company_name',
            'scores.section:id,title,description,max_points,sort_order',
            'sections:id,key,title,description,max_points,sort_order',
        ]);
    }

    protected function sections()
    {
        return AdjudicationSection::query()
            ->orderBy('sort_order')
            ->get(['id', 'key', 'title', 'description', 'max_points', 'sort_order']);
    }

    protected function smmes()
    {
        return BdsApplication::query()
            ->select(['id', 'company_name'])
            ->orderBy('company_name')
            ->get()
            ->map(fn (BdsApplication $application) => [
                'id' => $application->id,
                'name' => $application->company_name,
            ]);
    }

    protected function capabilities(AdjudicationAssessment $assessment): array
    {
        return [
            'can_update' => auth()->user()?->can('update', $assessment) ?? false,
            'can_submit' => auth()->user()?->can('submit', $assessment) ?? false,
            'can_unlock' => auth()->user()?->can('unlock', $assessment) ?? false,
            'can_delete' => auth()->user()?->can('delete', $assessment) ?? false,
        ];
    }
}
