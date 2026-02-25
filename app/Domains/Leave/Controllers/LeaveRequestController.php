<?php

namespace App\Domains\Leave\Controllers;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Services\LeaveBalanceService;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $balanceService
    ) {}

    public function index()
    {
        $user = Auth::user();
        $staff = $user?->staffMember?->load(['department', 'manager']);
        $isHrUser = $user && (
            $user->can('domain.human-resources.view')
            || $user->can('domain.human-resources.manage')
            || $user->can('domain.leave.view')
            || $user->can('domain.leave.manage')
        );

        $myRequests = $staff
            ? LeaveRequest::with(['staffMember.department', 'manager'])
                ->where('staff_member_id', $staff->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $managerQueue = $staff
            ? LeaveRequest::with(['staffMember.department', 'manager'])
                ->where('manager_id', $staff->id)
                ->where('status', 'submitted')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $hrQueue = $isHrUser
            ? LeaveRequest::with(['staffMember.department', 'manager'])
                ->where('status', 'manager_approved')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $leaveRegisterQuery = LeaveRequest::with(['staffMember.department', 'manager'])
            ->where('status', 'hr_approved')
            ->orderByDesc('start_date');

        if (! $isHrUser) {
            if ($staff && $staff->department_id) {
                $leaveRegisterQuery->whereHas('staffMember', function ($query) use ($staff) {
                    $query->where('department_id', $staff->department_id);
                });
            } elseif ($staff) {
                $leaveRegisterQuery->where('manager_id', $staff->id);
            } else {
                $leaveRegisterQuery->whereRaw('1 = 0');
            }
        }

        $leaveRegister = $leaveRegisterQuery->get();

        $mapLeave = fn ($leave) => [
            'id' => $leave->id,
            'staff_member_id' => $leave->staff_member_id,
            'staff_member_name' => $leave->staffMember
                ? trim($leave->staffMember->first_name.' '.$leave->staffMember->last_name)
                : null,
            'department_name' => $leave->staffMember?->department?->name,
            'manager_id' => $leave->manager_id,
            'manager_name' => $leave->manager
                ? trim($leave->manager->first_name.' '.$leave->manager->last_name)
                : null,
            'start_date' => $leave->start_date?->format('Y-m-d'),
            'end_date' => $leave->end_date?->format('Y-m-d'),
            'total_days' => $leave->total_days,
            'status' => $leave->status,
        ];

        return Inertia::render('LeaveRequests/Index', [
            'myRequests' => $myRequests->map($mapLeave)->values(),
            'managerQueue' => $managerQueue->map($mapLeave)->values(),
            'hrQueue' => $hrQueue->map($mapLeave)->values(),
            'leaveRegister' => $leaveRegister->map($mapLeave)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $staff = $user?->staffMember;
        if (! $staff) {
            return redirect()->back()->withErrors(['staff' => 'No staff profile found.']);
        }

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $totalDays = $start->diffInDays($end) + 1;

        $balance = $this->balanceService->calculate($staff);
        if ($totalDays > $balance['available']) {
            return redirect()->back()->withErrors([
                'end_date' => 'Insufficient leave balance.',
            ]);
        }

        LeaveRequest::create([
            'staff_member_id' => $staff->id,
            'manager_id' => $staff->manager_id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave request submitted');
    }

    public function managerApprove(Request $request, int $leave_request)
    {
        $data = $request->validate([
            'manager_comment' => 'nullable|string|max:2000',
        ]);

        $leave = LeaveRequest::findOrFail($leave_request);
        if ($leave->status !== 'submitted') {
            return redirect()->back()->withErrors([
                'status' => 'Leave request is not awaiting manager approval.',
            ]);
        }
        $leave->update([
            'status' => 'manager_approved',
            'manager_comment' => $data['manager_comment'] ?? null,
            'manager_approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave request approved by manager');
    }

    public function managerReject(Request $request, int $leave_request)
    {
        $data = $request->validate([
            'manager_comment' => 'nullable|string|max:2000',
        ]);

        $leave = LeaveRequest::findOrFail($leave_request);
        if ($leave->status !== 'submitted') {
            return redirect()->back()->withErrors([
                'status' => 'Leave request is not awaiting manager approval.',
            ]);
        }
        $leave->update([
            'status' => 'manager_rejected',
            'manager_comment' => $data['manager_comment'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Leave request rejected by manager');
    }

    public function hrApprove(Request $request, int $leave_request)
    {
        $data = $request->validate([
            'hr_comment' => 'nullable|string|max:2000',
        ]);

        $leave = LeaveRequest::findOrFail($leave_request);
        if ($leave->status !== 'manager_approved') {
            return redirect()->back()->withErrors([
                'status' => 'Leave request is not awaiting HR approval.',
            ]);
        }
        $leave->update([
            'status' => 'hr_approved',
            'hr_comment' => $data['hr_comment'] ?? null,
            'hr_approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave request approved by HR');
    }

    public function hrReject(Request $request, int $leave_request)
    {
        $data = $request->validate([
            'hr_comment' => 'nullable|string|max:2000',
        ]);

        $leave = LeaveRequest::findOrFail($leave_request);
        if ($leave->status !== 'manager_approved') {
            return redirect()->back()->withErrors([
                'status' => 'Leave request is not awaiting HR approval.',
            ]);
        }
        $leave->update([
            'status' => 'hr_rejected',
            'hr_comment' => $data['hr_comment'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Leave request rejected by HR');
    }

}
