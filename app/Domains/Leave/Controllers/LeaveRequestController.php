<?php

namespace App\Domains\Leave\Controllers;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Models\LeaveRequestDocument;
use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Leave\Services\LeaveRequestDocumentService;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveManagementService $leaveManagementService,
        protected LeaveRequestDocumentService $leaveRequestDocumentService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $staff = $user?->staffMember?->load(['department', 'manager']);
        $canViewHrQueue = $user && (
            $user->can('domain.human-resources.view')
            || $user->can('domain.human-resources.manage')
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

        $hrQueue = $canViewHrQueue
            ? LeaveRequest::with(['staffMember.department', 'manager'])
                ->where('status', 'manager_approved')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $leaveRegisterQuery = LeaveRequest::with(['staffMember.department', 'manager'])
            ->where('status', 'hr_approved')
            ->orderByDesc('start_date');

        if (! $canViewHrQueue) {
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

        return Inertia::render('LeaveRequests/Index', [
            'myRequests' => $myRequests->map(fn ($leave) => $this->leaveManagementService->mapLeave($leave))->values(),
            'managerQueue' => $managerQueue->map(fn ($leave) => $this->leaveManagementService->mapLeave($leave))->values(),
            'hrQueue' => $hrQueue->map(fn ($leave) => $this->leaveManagementService->mapLeave($leave))->values(),
            'leaveRegister' => $leaveRegister->map(fn ($leave) => $this->leaveManagementService->mapLeave($leave))->values(),
            'teamLeaveSummary' => $staff ? $this->leaveManagementService->teamSummaryForManager($staff) : [],
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
            'leave_type' => 'required|in:annual,sick',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $this->leaveManagementService->createRequest($staff, $data);

        return redirect()->back()->with('success', 'Leave request submitted');
    }

    public function show(Request $request, int $leave_request)
    {
        $leave = LeaveRequest::query()
            ->with(['staffMember.department', 'staffMember.user', 'manager', 'documents.documentFile.uploader', 'documents.uploader'])
            ->findOrFail($leave_request);

        abort_unless(
            $this->leaveManagementService->canViewLeaveRequest($request->user(), $leave),
            403
        );

        return Inertia::render('LeaveRequests/Show', [
            'leaveRequest' => $this->leaveManagementService->mapLeaveDetail($leave, $request->user()),
        ]);
    }

    public function managerApprove(Request $request, int $leave_request)
    {
        $data = $request->validate([
            'manager_comment' => 'nullable|string|max:2000',
        ]);

        $staff = $request->user()?->staffMember;
        if (! $staff) {
            return redirect()->back()->withErrors(['staff' => 'No staff profile found.']);
        }

        try {
            $this->leaveManagementService->managerApprove(
                $staff,
                LeaveRequest::query()->findOrFail($leave_request),
                $data['manager_comment'] ?? null
            );
        } catch (AuthorizationException $exception) {
            return redirect()->back()->withErrors(['authorization' => $exception->getMessage()]);
        }

        return redirect()->back()->with('success', 'Leave request approved by manager');
    }

    public function managerReject(Request $request, int $leave_request)
    {
        $data = $request->validate([
            'manager_comment' => 'nullable|string|max:2000',
        ]);

        $staff = $request->user()?->staffMember;
        if (! $staff) {
            return redirect()->back()->withErrors(['staff' => 'No staff profile found.']);
        }

        try {
            $this->leaveManagementService->managerReject(
                $staff,
                LeaveRequest::query()->findOrFail($leave_request),
                $data['manager_comment'] ?? null
            );
        } catch (AuthorizationException $exception) {
            return redirect()->back()->withErrors(['authorization' => $exception->getMessage()]);
        }

        return redirect()->back()->with('success', 'Leave request rejected by manager');
    }

    public function hrApprove(Request $request, int $leave_request)
    {
        abort_unless($request->user()?->can('domain.human-resources.manage'), 403);

        $data = $request->validate([
            'hr_comment' => 'nullable|string|max:2000',
        ]);

        $this->leaveManagementService->hrApprove(
            LeaveRequest::query()->findOrFail($leave_request),
            $data['hr_comment'] ?? null
        );

        return redirect()->back()->with('success', 'Leave request approved by HR');
    }

    public function hrReject(Request $request, int $leave_request)
    {
        abort_unless($request->user()?->can('domain.human-resources.manage'), 403);

        $data = $request->validate([
            'hr_comment' => 'nullable|string|max:2000',
        ]);

        $this->leaveManagementService->hrReject(
            LeaveRequest::query()->findOrFail($leave_request),
            $data['hr_comment'] ?? null
        );

        return redirect()->back()->with('success', 'Leave request rejected by HR');
    }

    public function revoke(Request $request, int $leave_request)
    {
        $staff = $request->user()?->staffMember;
        if (! $staff) {
            return redirect()->back()->withErrors(['staff' => 'No staff profile found.']);
        }

        try {
            $this->leaveManagementService->revokeRequest(
                $staff,
                LeaveRequest::query()->findOrFail($leave_request)
            );
        } catch (AuthorizationException $exception) {
            return redirect()->back()->withErrors(['authorization' => $exception->getMessage()]);
        }

        return redirect()->back()->with('success', 'Leave request revoked');
    }

    public function uploadDocument(Request $request, int $leave_request)
    {
        $leave = LeaveRequest::query()
            ->with(['staffMember.department', 'staffMember.user', 'manager'])
            ->findOrFail($leave_request);

        $data = $request->validate([
            'document_kind' => 'nullable|in:signed_leave_form,medical_certificate,manager_support,other',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:25600',
        ]);

        $this->leaveRequestDocumentService->upload($leave, $data, $request->user());

        return redirect()->back()->with('success', 'Leave supporting document uploaded.');
    }

    public function downloadDocument(Request $request, int $leave_request, LeaveRequestDocument $document)
    {
        abort_unless((int) $document->leave_request_id === (int) $leave_request, 404);

        return $this->leaveRequestDocumentService->download($document, $request->user());
    }

    public function deleteDocument(Request $request, int $leave_request, LeaveRequestDocument $document)
    {
        abort_unless((int) $document->leave_request_id === (int) $leave_request, 404);

        $this->leaveRequestDocumentService->delete($document, $request->user());

        return redirect()->back()->with('success', 'Leave supporting document deleted.');
    }
}
