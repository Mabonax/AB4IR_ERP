<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Requests\StoreProjectLocationRequest;
use App\Domains\Projects\Requests\UpdateProjectLocationRequest;
use App\Domains\Projects\Resources\ProjectLocationResource;
use App\Domains\Projects\Services\ProjectLocationService;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Inertia\Inertia;

class ProjectLocationController extends Controller
{
    public function __construct(
        protected ProjectLocationService $service
    ) {}

    public function index()
    {
        $locations = $this->service->paginateLocations();
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $facilitators = Facilitator::select('id', 'name', 'surname')
            ->orderBy('name')
            ->get()
            ->map(fn ($facilitator) => [
                'id' => $facilitator->id,
                'name' => trim($facilitator->name.' '.$facilitator->surname),
            ]);
        $provinces = Provinces::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('ProjectLocations/Index', [
            'locations' => ProjectLocationResource::collection($locations),
            'projects' => $projects,
            'facilitators' => $facilitators,
            'provinces' => $provinces,
        ]);
    }

    public function store(StoreProjectLocationRequest $request)
    {
        $this->service->createLocation($request->validated());

        return redirect()->back()->with('success', 'Project location created');
    }

    public function show(int $project_location)
    {
        $model = $this->service->getLocationById($project_location);

        return response()->json(new ProjectLocationResource($model));
    }

    public function update(UpdateProjectLocationRequest $request, int $project_location)
    {
        $this->service->updateLocation($project_location, $request->validated());

        return redirect()->back()->with('success', 'Project location updated');
    }

    public function destroy(int $project_location)
    {
        $this->service->deleteLocation($project_location);

        return redirect()->back()->with('success', 'Project location deleted');
    }
}
