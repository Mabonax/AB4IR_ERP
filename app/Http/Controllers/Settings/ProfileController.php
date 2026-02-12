<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Services\LeaveBalanceService;
use App\Domains\Staff\Models\StaffMember;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected LeaveBalanceService $balanceService
    ) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $staff = $user?->staffMember?->load(['department', 'manager']);

        $myRequests = $staff
            ? LeaveRequest::with(['staffMember', 'manager'])
                ->where('staff_member_id', $staff->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $mapLeave = fn ($leave) => [
            'id' => $leave->id,
            'staff_member_id' => $leave->staff_member_id,
            'staff_member_name' => $leave->staffMember
                ? trim($leave->staffMember->first_name.' '.$leave->staffMember->last_name)
                : null,
            'manager_id' => $leave->manager_id,
            'manager_name' => $leave->manager
                ? trim($leave->manager->first_name.' '.$leave->manager->last_name)
                : null,
            'start_date' => $leave->start_date?->format('Y-m-d'),
            'end_date' => $leave->end_date?->format('Y-m-d'),
            'total_days' => $leave->total_days,
            'status' => $leave->status,
        ];

        $balance = $staff ? $this->balanceService->calculate($staff) : [
            'accrued' => 0,
            'used' => 0,
            'available' => 0,
            'period_start' => null,
            'period_end' => null,
        ];

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'staff' => $staff ? [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'department' => $staff->department?->name,
                'manager' => $staff->manager
                    ? trim($staff->manager->first_name.' '.$staff->manager->last_name)
                    : null,
                'start_date' => $staff->start_date?->format('Y-m-d'),
                'is_ceo' => (bool) $staff->is_ceo,
                'is_board_member' => (bool) $staff->is_board_member,
            ] : null,
            'balance' => $balance,
            'myRequests' => $myRequests->map($mapLeave)->values(),
        ]);
    }

    public function leave(Request $request): Response
    {
        $user = $request->user();
        $staff = $user?->staffMember?->load(['department', 'manager']);

        $myRequests = $staff
            ? LeaveRequest::with(['staffMember', 'manager'])
                ->where('staff_member_id', $staff->id)
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $mapLeave = fn ($leave) => [
            'id' => $leave->id,
            'staff_member_id' => $leave->staff_member_id,
            'staff_member_name' => $leave->staffMember
                ? trim($leave->staffMember->first_name.' '.$leave->staffMember->last_name)
                : null,
            'manager_id' => $leave->manager_id,
            'manager_name' => $leave->manager
                ? trim($leave->manager->first_name.' '.$leave->manager->last_name)
                : null,
            'start_date' => $leave->start_date?->format('Y-m-d'),
            'end_date' => $leave->end_date?->format('Y-m-d'),
            'total_days' => $leave->total_days,
            'status' => $leave->status,
        ];

        $balance = $staff ? $this->balanceService->calculate($staff) : [
            'accrued' => 0,
            'used' => 0,
            'available' => 0,
            'period_start' => null,
            'period_end' => null,
        ];

        return Inertia::render('settings/leave', [
            'balance' => $balance,
            'myRequests' => $myRequests->map($mapLeave)->values(),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
