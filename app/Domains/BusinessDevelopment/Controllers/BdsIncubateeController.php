<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Requests\StoreBdsIncubateeRequest;
use App\Domains\BusinessDevelopment\Requests\UpdateBdsIncubateeRequest;
use App\Domains\BusinessDevelopment\Resources\BdsIncubateeResource;
use App\Domains\BusinessDevelopment\Services\BdsIncubateeService;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Inertia\Inertia;

class BdsIncubateeController extends Controller
{
    public function __construct(
        protected BdsIncubateeService $service
    ) {}

    public function index()
    {
        return Inertia::render('BusinessDevelopment/Incubatees/Index', [
            'incubatees' => BdsIncubateeResource::collection(
                $this->service->paginate()
            ),
            'provinces' => Provinces::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBdsIncubateeRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Incubatee created');
    }

    public function show(int $incubatee)
    {
        $model = $this->service->getById($incubatee);

        return response()->json(new BdsIncubateeResource($model));
    }

    public function update(UpdateBdsIncubateeRequest $request, int $incubatee)
    {
        $this->service->update($incubatee, $request->validated());

        return redirect()->back()->with('success', 'Incubatee updated');
    }

    public function destroy(int $incubatee)
    {
        $this->service->delete($incubatee);

        return redirect()->back()->with('success', 'Incubatee deleted');
    }
}

