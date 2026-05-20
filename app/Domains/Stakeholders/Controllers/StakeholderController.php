<?php

namespace App\Domains\Stakeholders\Controllers;

use App\Domains\Stakeholders\Requests\StoreStakeholderRequest;
use App\Domains\Stakeholders\Requests\UpdateStakeholderRequest;
use App\Domains\Stakeholders\Resources\StakeholderResource;
use App\Domains\Stakeholders\Services\StakeholderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        if (request()->wantsJson()) {
            return response()->json(new StakeholderResource($stakeholderModel));
        }

        return Inertia::render('Stakeholders/Show', [
            'stakeholder' => (new StakeholderResource($stakeholderModel))->resolve(),
        ]);
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

    public function storeContact(Request $request, int $stakeholder)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'contact_number' => ['required', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->addStakeholderContact($stakeholder, $validated);

        return redirect()->back()->with('success', 'Stakeholder contact added.');
    }

    public function destroyContact(int $stakeholder, int $contact)
    {
        $this->service->deleteStakeholderContact($stakeholder, $contact);

        return redirect()->back()->with('success', 'Stakeholder contact deleted.');
    }
}
