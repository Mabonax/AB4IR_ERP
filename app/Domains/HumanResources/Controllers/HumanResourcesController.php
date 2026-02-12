<?php

namespace App\Domains\HumanResources\Controllers;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class HumanResourcesController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('HumanResources/Dashboard', [
            'stats' => [
                'totalStaff' => StaffMember::count(),
                'activeStaff' => StaffMember::where('status', 'active')->count(),
                'inactiveStaff' => StaffMember::where('status', 'inactive')->count(),
                'pendingManager' => LeaveRequest::where('status', 'submitted')->count(),
                'pendingHr' => LeaveRequest::where('status', 'manager_approved')->count(),
                'approved' => LeaveRequest::where('status', 'hr_approved')->count(),
            ],
        ]);
    }
}
