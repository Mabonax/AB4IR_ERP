<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Beneficiaries\Models\Beneficiary;
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
        $enrollments = $this->service->paginateEnrollments();
        $projects = Project::select('id', 'name')->orderBy('name')->get();
        $beneficiaries = Beneficiary::select('id', 'name', 'surname')
            ->orderBy('name')
            ->get()
            ->map(fn ($beneficiary) => [
                'id' => $beneficiary->id,
                'name' => trim($beneficiary->name.' '.$beneficiary->surname),
            ]);

        return Inertia::render('ProjectEnrollments/Index', [
            'enrollments' => ProjectEnrollmentResource::collection($enrollments),
            'projects' => $projects,
            'beneficiaries' => $beneficiaries,
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
