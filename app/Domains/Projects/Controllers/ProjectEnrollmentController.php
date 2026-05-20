<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Requests\StoreProjectEnrollmentRequest;
use App\Domains\Projects\Requests\UpdateProjectEnrollmentRequest;
use App\Domains\Projects\Resources\ProjectEnrollmentResource;
use App\Domains\Projects\Services\ProjectEnrollmentService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ProjectEnrollmentController extends Controller
{
    public function __construct(
        protected ProjectEnrollmentService $service
    ) {}

    public function index()
    {
        $projects = Project::with([
            'locations.enrollments.beneficiary',
            'locations.facilitator',
            'locations.province',
        ])
            ->orderBy('name')
            ->get()
            ->map(function ($project) {
                $locations = $project->locations->map(function ($location) {
                    $beneficiaries = $location->enrollments->map(function ($enrollment) {
                        return [
                            'id' => $enrollment->beneficiary_id,
                            'name' => $enrollment->beneficiary
                                ? trim($enrollment->beneficiary->name.' '.$enrollment->beneficiary->surname)
                                : null,
                        ];
                    })->filter(fn ($beneficiary) => $beneficiary['name'] !== null)->values();

                    return [
                        'id' => $location->id,
                        'location' => $location->province?->name,
                        'facilitator_name' => $location->facilitator
                            ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                            : null,
                        'beneficiary_count' => $beneficiaries->count(),
                        'beneficiaries' => $beneficiaries,
                    ];
                });

                $totalBeneficiaries = $locations->sum('beneficiary_count');

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date?->format('Y-m-d'),
                    'status' => $project->status,
                    'locations' => $locations,
                    'locations_count' => $locations->count(),
                    'beneficiary_count' => $totalBeneficiaries,
                ];
            });

        return Inertia::render('ProjectEnrollments/Index', [
            'projects' => $projects,
        ]);
    }

    public function store(StoreProjectEnrollmentRequest $request)
    {
        $this->service->createEnrollment($request->validated());

        return redirect()->back()->with('success', 'Beneficiary enrolled');
    }

    public function show(int $project_enrollment)
    {
        $model = $this->service->getEnrollmentById($project_enrollment);

        return response()->json(new ProjectEnrollmentResource($model));
    }

    public function update(UpdateProjectEnrollmentRequest $request, int $project_enrollment)
    {
        $this->service->updateEnrollment($project_enrollment, $request->validated());

        return redirect()->back()->with('success', 'Enrollment updated');
    }

    public function destroy(int $project_enrollment)
    {
        $this->service->deleteEnrollment($project_enrollment);

        return redirect()->back()->with('success', 'Enrollment deleted');
    }
}
