<?php

namespace App\Domains\Staff\Controllers;

use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Staff\Requests\StoreStaffRequest;
use App\Domains\Staff\Requests\UpdateStaffRequest;
use App\Domains\Staff\Resources\StaffMemberResource;
use App\Domains\Staff\Services\StaffService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(
        protected StaffService $service,
        protected LeaveManagementService $leaveManagementService
    ) {}

    protected function formOptions(): array
    {
        $departments = \App\Domains\Staff\Models\StaffDepartment::select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        $managers = StaffMember::select('id', 'first_name', 'last_name')
            ->addSelect('department_id', 'is_manager')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'department_id' => $staff->department_id,
                'is_manager' => (bool) $staff->is_manager,
            ]);

        return [$departments, $managers];
    }

    public function index(Request $request)
    {
        $staffMembers = $this->service->paginateStaffMembers();
        [$departments] = $this->formOptions();

        return Inertia::render('Staff/Index', [
            'staffMembers' => StaffMemberResource::collection($staffMembers),
            'departments' => $departments,
            'selectedDepartmentId' => $request->integer('department_id') ?: null,
        ]);
    }

    public function create(Request $request)
    {
        [$departments, $managers] = $this->formOptions();

        return Inertia::render('Staff/Create', [
            'departments' => $departments,
            'managers' => $managers,
            'selectedDepartmentId' => $request->integer('department_id') ?: null,
        ]);
    }

    public function dashboard(Request $request)
    {
        $staff = $request->user()?->staffMember;
        $managerLeave = $this->leaveManagementService->managerDashboardSummary($staff);

        return Inertia::render('Staff/Dashboard', [
            'stats' => [
                'totalStaff' => StaffMember::count(),
                'activeStaff' => StaffMember::where('status', 'active')->count(),
                'inactiveStaff' => StaffMember::where('status', 'inactive')->count(),
                'departmentCount' => \App\Domains\Staff\Models\StaffDepartment::count(),
            ],
            'managerLeave' => $managerLeave,
        ]);
    }

    public function profile()
    {
        return redirect()->to('/settings/profile');
    }

    public function profileShow(int $staff)
    {
        $model = StaffMember::with(['department', 'manager', 'nextOfKin'])
            ->findOrFail($staff);

        return Inertia::render('Staff/Profile', [
            'staff' => array_merge(
                (new StaffMemberResource($model))->resolve(),
                ['leave_account' => $this->leaveManagementService->summarizeStaff($model)]
            ),
            'isSelf' => false,
            'canManageStaff' => $this->canManageStaff(),
            'canPromoteManager' => $this->canPromoteManagers() && ! $model->is_manager && ! $model->is_ceo,
        ]);
    }

    public function store(StoreStaffRequest $request)
    {
        $this->service->createStaffWithNextOfKin($request->validated());

        return redirect()->back()->with('success', 'Staff member created');
    }

    public function show(StaffMember $staff)
    {
        return response()->json(new StaffMemberResource(
            $staff->load(['department', 'manager', 'nextOfKin'])
        ));
    }

    public function edit(StaffMember $staff)
    {
        [$departments, $managers] = $this->formOptions();

        return Inertia::render('Staff/Edit', [
            'staffMember' => new StaffMemberResource(
                $staff->load(['department', 'manager', 'nextOfKin'])
            ),
            'departments' => $departments,
            'managers' => $managers,
        ]);
    }

    public function update(UpdateStaffRequest $request, StaffMember $staff)
    {
        $this->service->updateStaffWithNextOfKin($staff->id, $request->validated());

        return redirect()->back()->with('success', 'Staff member updated');
    }

    public function destroy(StaffMember $staff)
    {
        $this->service->deleteStaff($staff->id);

        return redirect()->back()->with('success', 'Staff member deleted');
    }

    public function promote(StaffMember $staff)
    {
        abort_unless($this->canPromoteManagers(), 403);

        $this->service->promoteToManager($staff->id);

        return redirect()->back()->with('success', 'Staff member promoted to manager');
    }

    protected function canPromoteManagers(): bool
    {
        $user = Auth::user();
        $staff = $user?->staffMember;

        return (bool) $staff?->is_ceo || (bool) $user?->hasRole('super-admin');
    }

    protected function canManageStaff(): bool
    {
        $user = Auth::user();

        return (bool) $user?->can('domain.staff.manage');
    }
}
