<?php

namespace App\Domains\HumanResources\Controllers;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class HumanResourcesController extends Controller
{
    public function __construct(
        protected LeaveManagementService $leaveManagementService
    ) {}

    public function dashboard()
    {
        $departments = StaffDepartment::query()
            ->withCount('staffMembers')
            ->orderBy('name')
            ->get()
            ->map(fn (StaffDepartment $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'staff_count' => $department->staff_members_count,
            ]);

        $managerOptions = StaffMember::query()
            ->select('id', 'first_name', 'last_name', 'department_id')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (StaffMember $staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'department_id' => $staff->department_id,
            ]);

        $organizationLeave = $this->leaveManagementService->organizationSummary();

        return Inertia::render('HumanResources/Dashboard', [
            'stats' => [
                'totalStaff' => StaffMember::count(),
                'activeStaff' => StaffMember::where('status', 'active')->count(),
                'inactiveStaff' => StaffMember::where('status', 'inactive')->count(),
                'pendingManager' => LeaveRequest::where('status', 'submitted')->count(),
                'pendingHr' => LeaveRequest::where('status', 'manager_approved')->count(),
                'approved' => LeaveRequest::where('status', 'hr_approved')->count(),
            ],
            'departments' => $departments,
            'managers' => $managerOptions,
            'leaveSummary' => $organizationLeave,
        ]);
    }
}
