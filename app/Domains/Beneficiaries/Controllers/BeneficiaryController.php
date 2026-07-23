<?php

namespace App\Domains\Beneficiaries\Controllers;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Requests\BeneficiaryLifecycleActionRequest;
use App\Domains\Beneficiaries\Requests\ImportBeneficiaryRequest;
use App\Domains\Beneficiaries\Requests\StoreBeneficiaryRequest;
use App\Domains\Beneficiaries\Requests\TransferBeneficiaryRequest;
use App\Domains\Beneficiaries\Requests\UpdateBeneficiaryRequest;
use App\Domains\Beneficiaries\Resources\BeneficiaryResource;
use App\Domains\Beneficiaries\Services\BeneficiaryLifecycleService;
use App\Domains\Beneficiaries\Services\BeneficiaryService;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected BeneficiaryService $service,
        protected BeneficiaryLifecycleService $lifecycleService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Beneficiary::class);

        $selectedProgramId = $request->integer('program_id') ?: null;
        $selectedProjectId = $request->integer('project_id') ?: null;
        $filterProjects = Project::query()
            ->select('id', 'name', 'program_id', 'start_date', 'end_date', 'status')
            ->when($selectedProgramId, fn ($query) => $query->where('program_id', $selectedProgramId))
            ->orderBy('name')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'program_id' => $project->program_id,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'status' => $project->status,
            ]);
        $selectedProjectLocations = $selectedProjectId
            ? ProjectLocation::with('province:id,name')
                ->where('project_id', $selectedProjectId)
                ->orderBy('province_id')
                ->get()
                ->map(fn ($location) => [
                    'id' => $location->id,
                    'name' => $location->province?->name ?? "Location {$location->id}",
                ])
                ->values()
            : collect();

        return Inertia::render('Beneficiaries/Index', [
            'beneficiary' => BeneficiaryResource::collection(
                $this->service->paginateBeneficiaries($selectedProjectId)
            ),
            'programs' => Program::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get()
                ->map(fn ($program) => [
                    'id' => $program->id,
                    'title' => $program->title,
                ]),
            'selectedProgramId' => $selectedProgramId,
            'selectedProjectId' => $selectedProjectId,
            'filterProjects' => $filterProjects,
            'selectedProjectLocations' => $selectedProjectLocations,
            'selectedProjectSummary' => $selectedProjectId
                ? $filterProjects->firstWhere('id', $selectedProjectId)
                : null,
            'lifecycleMetrics' => $selectedProjectId
                ? $this->lifecycleService->cohortMetrics($selectedProjectId)
                : null,
        ]);
    }

    public function import(ImportBeneficiaryRequest $request): RedirectResponse
    {
        $this->authorize('create', Beneficiary::class);

        $summary = $this->service->importFromFile(
            $request->file('file'),
            (int) $request->integer('project_id'),
            (int) $request->integer('project_location_id')
        );

        return redirect()->back()->with([
            'success' => sprintf(
                'Beneficiary import completed. Processed: %d, Created: %d, Matched existing: %d, Rejected duplicates: %d, Errors: %d.',
                $summary['processed'],
                $summary['created'],
                $summary['matched_existing'],
                $summary['rejected_duplicates'],
                count($summary['errors'])
            ),
            'import_errors' => $summary['errors'],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Beneficiary::class);

        return Inertia::render('Beneficiaries/Create', $this->formOptions());
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse
    {
        $this->authorize('create', Beneficiary::class);

        $beneficiary = $this->service->store($request->validated());

        return redirect()
            ->route('beneficiaries.show', $beneficiary->id)
            ->with('success', 'Beneficiary created');
    }

    public function show(Request $request, int $beneficiary)
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('view', $model);

        $resource = new BeneficiaryResource($model);

        if ($request->expectsJson()) {
            return response()->json($resource);
        }

        return Inertia::render('Beneficiaries/Show', [
            'beneficiary' => $resource->resolve(),
            'canManageBeneficiary' => $request->user()?->can('update', $model) ?? false,
            'lifecycleOptions' => [
                'outcomeTypes' => collect(\App\Domains\Beneficiaries\Models\BeneficiaryOutcome::TYPES)
                    ->map(fn ($type) => [
                        'value' => $type,
                        'label' => str($type)->replace('_', ' ')->title()->value(),
                    ])->values(),
                'projects' => Project::query()
                    ->select('id', 'name', 'program_id', 'status')
                    ->whereIn('status', \App\Domains\Projects\Services\ProjectEnrollmentConsistencyService::BENEFICIARY_ASSIGNABLE_STATUSES)
                    ->whereKeyNot($model->project_id)
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($project) => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'program_id' => $project->program_id,
                        'status' => $project->status,
                    ])->values(),
                'projectLocations' => ProjectLocation::with(['project:id,name', 'province:id,name'])
                    ->select('id', 'project_id', 'province_id')
                    ->orderBy('project_id')
                    ->orderBy('province_id')
                    ->get()
                    ->map(fn ($location) => [
                        'id' => $location->id,
                        'project_id' => $location->project_id,
                        'name' => ($location->project?->name ? $location->project->name.' - ' : '').($location->province?->name ?? "Location {$location->id}"),
                    ])->values(),
            ],
        ]);
    }

    public function edit(int $beneficiary): Response
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('update', $model);

        return Inertia::render('Beneficiaries/Edit', [
            'beneficiary' => (new BeneficiaryResource($model))->resolve(),
            ...$this->formOptions((int) $model->project_id),
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('update', $model);

        $updated = $this->service->update($beneficiary, $request->validated());

        return redirect()
            ->route('beneficiaries.show', $updated->id)
            ->with('success', 'Beneficiary updated');
    }

    public function destroy(int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('delete', $model);

        $this->service->delete($beneficiary);

        return redirect()
            ->route('beneficiaries.index')
            ->with('success', 'Beneficiary deleted');
    }

    public function suspend(BeneficiaryLifecycleActionRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('manageLifecycle', $model);

        $this->lifecycleService->suspendBeneficiary($model, $request->user(), $request->string('reason')->toString());

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary suspended.');
    }

    public function reinstate(BeneficiaryLifecycleActionRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('manageLifecycle', $model);

        $this->lifecycleService->reactivateBeneficiary($model, $request->user(), $request->string('reason')->toString());

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary reinstated.');
    }

    public function graduate(BeneficiaryLifecycleActionRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('manageLifecycle', $model);

        $this->lifecycleService->graduateBeneficiary(
            $model,
            $request->user(),
            $request->string('reason')->toString(),
            $request->string('outcome_type')->toString() ?: null,
            $request->string('outcome_notes')->toString() ?: null,
        );

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary graduated.');
    }

    public function exit(BeneficiaryLifecycleActionRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('manageLifecycle', $model);

        $this->lifecycleService->exitBeneficiary(
            $model,
            $request->user(),
            $request->string('reason')->toString(),
            $request->string('outcome_type')->toString() ?: null,
            $request->string('outcome_notes')->toString() ?: null,
        );

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary exited.');
    }

    public function transfer(TransferBeneficiaryRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('manageLifecycle', $model);

        $this->lifecycleService->transferBeneficiary(
            $model,
            $request->user(),
            (int) $request->integer('project_id'),
            (int) $request->integer('project_location_id'),
            $request->string('reason')->toString(),
        );

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiary transferred.');
    }

    public function archive(BeneficiaryLifecycleActionRequest $request, int $beneficiary): RedirectResponse
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('manageLifecycle', $model);

        $this->lifecycleService->archiveBeneficiary($model, $request->user(), $request->string('reason')->toString());

        return redirect()->route('beneficiaries.index')->with('success', 'Beneficiary archived.');
    }

    protected function formOptions(?int $currentProjectId = null): array
    {
        $projects = Project::query()
            ->select('id', 'name', 'program_id', 'status')
            ->where(function ($query) use ($currentProjectId) {
                $query->whereIn('status', \App\Domains\Projects\Services\ProjectEnrollmentConsistencyService::BENEFICIARY_ASSIGNABLE_STATUSES);

                if ($currentProjectId !== null) {
                    $query->orWhere('id', $currentProjectId);
                }
            })
            ->orderBy('name')
            ->get();

        return [
            'programs' => Program::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get()
                ->map(fn ($program) => [
                    'id' => $program->id,
                    'title' => $program->title,
                ]),
            'provinces' => Provinces::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'program_id' => $project->program_id,
                'status' => $project->status,
            ])->values(),
            'projectLocations' => ProjectLocation::with(['project:id,name', 'province:id,name'])
                ->select('id', 'project_id', 'province_id')
                ->whereIn('project_id', $projects->pluck('id'))
                ->orderBy('province_id')
                ->get()
                ->map(fn ($location) => [
                    'id' => $location->id,
                    'project_id' => $location->project_id,
                    'name' => ($location->project?->name
                            ? $location->project->name.' - '
                            : '')
                        .$location->province?->name,
                ]),
        ];
    }
}
