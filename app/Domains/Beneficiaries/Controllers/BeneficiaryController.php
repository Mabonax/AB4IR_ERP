<?php

namespace App\Domains\Beneficiaries\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Services\BeneficiaryService;
use App\Domains\Beneficiaries\Requests\StoreBeneficiaryRequest;
use App\Domains\Beneficiaries\Requests\UpdateBeneficiaryRequest;

use App\Models\Provinces;
use App\Domains\Projects\Models\ProjectLocation;


use App\Domains\Beneficiaries\Resources\BeneficiaryResource;
use App\Domains\Projects\Models\Project;
use Inertia\Inertia;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected BeneficiaryService $service
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Beneficiary::class);

        return Inertia::render('Beneficiaries/Index', [
            'beneficiary' => BeneficiaryResource::collection(
                $this->service->paginateBeneficiaries()
            ),
            'provinces' => Provinces::select('id', 'name')->get(),
            'projects' => Project::select('id', 'name')->orderBy('name')->get(),
            'projectLocations' => ProjectLocation::with(['project:id,name', 'province:id,name'])
                ->select('id', 'project_id', 'province_id')
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
        ]);
    }

    public function store(StoreBeneficiaryRequest $request)
    {
        $this->authorize('create', Beneficiary::class);

        $this->service->store($request->validated());

        return redirect()->back()->with('success', 'Beneficiary created');
    }

    public function show(int $beneficiary)
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('view', $model);

        return response()->json(new BeneficiaryResource($model));
    }

    public function update(UpdateBeneficiaryRequest $request, int $beneficiary)
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('update', $model);

        $this->service->update($beneficiary, $request->validated());

        return redirect()->back()->with('success', 'Beneficiary updated');
    }

    public function destroy(int $beneficiary)
    {
        $model = $this->service->getById($beneficiary);
        $this->authorize('delete', $model);

        $this->service->delete($beneficiary);

        return redirect()->back()->with('success', 'Beneficiary deleted');
    }
}
