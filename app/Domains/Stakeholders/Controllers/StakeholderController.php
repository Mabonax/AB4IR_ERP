<?php

namespace App\Domains\Stakeholders\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Stakeholders\Services\StakeholderService;
use App\Domains\Stakeholders\Requests\StoreStakeholderRequest;
use App\Domains\Stakeholders\Requests\UpdateStakeholderRequest;
use App\Domains\Stakeholders\Resources\StakeholderResource;
use Inertia\Inertia;

class StakeholderController extends Controller
{
    public function __construct(
        protected StakeholderService $service
    ) {}

    public function index()
    {
        $stakeholders = $this->service->paginateStakeholders();

        return Inertia::render('Stakeholders/Index', [
            'stakeholders' => StakeholderResource::collection($stakeholders),
        ]);
    }

    public function store(StoreStakeholderRequest $request)
    {
        $this->service->createStakeholderWithContact($request->validated());

        return redirect()->back()->with('success', 'Stakeholder created');
    }

    public function show(int $stakeholder)
    {
        $stakeholderModel = $this->service->getStakeholderById($stakeholder);

        return response()->json(new StakeholderResource($stakeholderModel));
    }

    public function update(UpdateStakeholderRequest $request, int $stakeholder)
    {
        $this->service->updateStakeholderWithContact($stakeholder, $request->validated());

        return redirect()->back()->with('success', 'Stakeholder updated');
    }

    public function destroy(int $stakeholder)
    {
        $this->service->deleteStakeholder($stakeholder);

        return redirect()->back()->with('success', 'Stakeholder deleted');
    }
}
