<?php

namespace App\Domains\Staff\Controllers;

use App\Domains\Staff\Requests\StoreStaffRequest;
use App\Domains\Staff\Requests\UpdateStaffRequest;
use App\Domains\Staff\Resources\StaffMemberResource;
use App\Domains\Staff\Services\StaffService;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(
        protected StaffService $service
    ) {}

    public function index()
    {
        $staffMembers = $this->service->paginateStaffMembers();
        $departments = \App\Domains\Staff\Models\StaffDepartment::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Staff/Index', [
            'staffMembers' => StaffMemberResource::collection($staffMembers),
            'departments' => $departments,
        ]);
    }

    public function dashboard()
    {
        return Inertia::render('Staff/Dashboard', [
            'stats' => [
                'totalStaff' => StaffMember::count(),
                'activeStaff' => StaffMember::where('status', 'active')->count(),
                'inactiveStaff' => StaffMember::where('status', 'inactive')->count(),
                'departmentCount' => \App\Domains\Staff\Models\StaffDepartment::count(),
            ],
        ]);
    }

    public function store(StoreStaffRequest $request)
    {
        $this->service->createStaffWithNextOfKin($request->validated());

        return redirect()->back()->with('success', 'Staff member created');
    }

    public function show(int $staff)
    {
        $model = $this->service->getStaffById($staff);

        return response()->json(new StaffMemberResource($model));
    }

    public function update(UpdateStaffRequest $request, int $staff)
    {
        $this->service->updateStaffWithNextOfKin($staff, $request->validated());

        return redirect()->back()->with('success', 'Staff member updated');
    }

    public function destroy(int $staff)
    {
        $this->service->deleteStaff($staff);

        return redirect()->back()->with('success', 'Staff member deleted');
    }
}
