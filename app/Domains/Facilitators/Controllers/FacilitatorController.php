<?php

namespace App\Domains\Facilitators\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Facilitators\Services\FacilitatorService;
use App\Domains\Facilitators\Requests\StoreFacilitatorRequest;
use App\Domains\Facilitators\Requests\UpdateFacilitatorRequest;
use App\Domains\Facilitators\Resources\FacilitatorResource;
use Inertia\Inertia;

class FacilitatorController extends Controller
{
    public function __construct(
        protected FacilitatorService $service
    ) {}

    public function index()
    {
        return Inertia::render('Facilitators/Index', [
            'facilitators' => FacilitatorResource::collection(
                $this->service->paginateFacilitators()
            ),
        ]);
    }

    public function store(StoreFacilitatorRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Facilitator created');
    }

    public function show(int $facilitator)
    {
        $model = $this->service->getById($facilitator);

        return response()->json(new FacilitatorResource($model));
    }

    public function update(UpdateFacilitatorRequest $request, int $facilitator)
    {
        $this->service->update($facilitator, $request->validated());

        return redirect()->back()->with('success', 'Facilitator updated');
    }

    public function destroy(int $facilitator)
    {
        $this->service->delete($facilitator);

        return redirect()->back()->with('success', 'Facilitator deleted');
    }
}
