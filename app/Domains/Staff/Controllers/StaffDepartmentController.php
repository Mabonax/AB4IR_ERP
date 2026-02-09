<?php

namespace App\Domains\Staff\Controllers;

use App\Domains\Staff\Requests\StoreStaffDepartmentRequest;
use App\Domains\Staff\Requests\UpdateStaffDepartmentRequest;
use App\Domains\Staff\Resources\StaffDepartmentResource;
use App\Domains\Staff\Services\StaffDepartmentService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class StaffDepartmentController extends Controller
{
    public function __construct(
        protected StaffDepartmentService $service
    ) {}

    public function index()
    {
        $departments = $this->service->paginateDepartments();

        return Inertia::render('StaffDepartments/Index', [
            'departments' => StaffDepartmentResource::collection($departments),
        ]);
    }

    public function store(StoreStaffDepartmentRequest $request)
    {
        $this->service->createDepartment($request->validated());

        return redirect()->back()->with('success', 'Department created');
    }

    public function show(int $staff_department)
    {
        $model = $this->service->getDepartmentById($staff_department);

        return response()->json(new StaffDepartmentResource($model));
    }

    public function update(UpdateStaffDepartmentRequest $request, int $staff_department)
    {
        $this->service->updateDepartment($staff_department, $request->validated());

        return redirect()->back()->with('success', 'Department updated');
    }

    public function destroy(int $staff_department)
    {
        $this->service->deleteDepartment($staff_department);

        return redirect()->back()->with('success', 'Department deleted');
    }
}
