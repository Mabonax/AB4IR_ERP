<?php

namespace App\Domains\Beneficiaries\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Services\BeneficiaryService;
use App\Domains\Beneficiaries\Requests\StoreBeneficiaryRequest;
use App\Domains\Beneficiaries\Requests\UpdateBeneficiaryRequest;
use App\Domains\Beneficiaries\Resources\BeneficiaryResource;
use Inertia\Inertia;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected BeneficiaryService $service
    ) {}

    public function index()
    {
        return Inertia::render('Beneficiaries/Index', [
            'beneficiaries' => BeneficiaryResource::collection(
                $this->service->list()
            )
        ]);
    }

    public function store(StoreBeneficiaryRequest $request)
    {
        $this->service->store($request->validated());

        return redirect()->back()->with('success', 'Beneficiary created');
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary)
    {
        $this->service->update($beneficiary, $request->validated());

        return redirect()->back()->with('success', 'Beneficiary updated');
    }

    public function destroy(Beneficiary $beneficiary)
    {
        $this->service->delete($beneficiary);

        return redirect()->back()->with('success', 'Beneficiary deleted');
    }
}
