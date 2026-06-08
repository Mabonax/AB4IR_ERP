<?php

namespace App\Domains\StaffAttendance\Controllers;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use App\Domains\StaffAttendance\Services\StaffAttendanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StaffAttendanceController extends Controller
{
    public function __construct(
        protected StaffAttendanceService $service
    ) {}

    public function self(Request $request): Response
    {
        $this->authorize('clock', StaffAttendanceRecord::class);

        return Inertia::render('settings/attendance', $this->service->selfServicePayload($request->user()));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $this->authorize('clock', StaffAttendanceRecord::class);

        $this->service->clockIn($request->user());

        return redirect()->back()->with('success', 'Clock-in recorded successfully.');
    }

    public function requestLateClockIn(Request $request): RedirectResponse
    {
        $this->authorize('clock', StaffAttendanceRecord::class);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->service->requestLateClockIn($request->user(), $validated['reason']);

        return redirect()->back()->with('success', 'Late clock-in request sent to your line manager.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $this->authorize('clock', StaffAttendanceRecord::class);

        $this->service->clockOut(
            $request->user(),
            (bool) $request->boolean('use_default_time')
        );

        return redirect()->back()->with('success', 'Clock-out recorded successfully.');
    }

    public function management(Request $request): Response
    {
        $this->authorize('viewAny', StaffAttendanceRecord::class);

        return Inertia::render('HumanResources/Attendance', $this->service->managementPayload(
            $request->user(),
            $request->only(['period', 'anchor_date', 'department_id', 'staff_id'])
        ));
    }

    public function approveLateClockInRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff_members,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $staff = StaffMember::query()->findOrFail((int) $validated['staff_id']);
        $this->authorize('openLateOverride', [StaffAttendanceRecord::class, $staff]);

        $this->service->approveLateClockInRequest($request->user(), $staff, $validated['reason']);

        return redirect()->back()->with('success', 'Late clock-in request approved successfully.');
    }

    public function exportReportPdf(Request $request): SymfonyResponse
    {
        $staff = null;
        if ($request->filled('staff_id')) {
            $staff = StaffMember::query()->findOrFail((int) $request->integer('staff_id'));
        }

        $this->authorize('viewReports', [StaffAttendanceRecord::class, $staff]);

        return $this->service->exportReportPdf(
            $request->user(),
            $request->only(['period', 'anchor_date', 'department_id', 'staff_id'])
        );
    }
}
