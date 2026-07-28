<?php

namespace App\Domains\Compliance\Controllers;

use App\Domains\Compliance\Models\ComplianceRecord;
use App\Domains\Compliance\Requests\StoreComplianceRecordRequest;
use App\Domains\Compliance\Requests\UpdateComplianceRecordRequest;
use App\Domains\Compliance\Services\ComplianceService;
use App\Domains\Organisation\Services\OrganisationService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceRegistryController extends Controller
{
    public function __construct(
        protected ComplianceService $service,
        protected OrganisationService $organisationService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', ComplianceRecord::class);

        $registry = $this->service->registry();

        return Inertia::render('Organization/Compliance', [
            'stats' => $registry['stats'],
            'records' => $registry['records'],
            'organisations' => $this->organisationService->all()
                ->map(fn ($organisation) => [
                    'id' => $organisation->id,
                    'name' => $organisation->name,
                ])->values()->all(),
        ]);
    }

    public function store(StoreComplianceRecordRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Compliance record added.');
    }

    public function update(UpdateComplianceRecordRequest $request, int $record)
    {
        $model = $this->service->findOrFail($record);
        $this->authorize('update', $model);

        $this->service->update($record, $request->validated());

        return redirect()->back()->with('success', 'Compliance record updated.');
    }
}
