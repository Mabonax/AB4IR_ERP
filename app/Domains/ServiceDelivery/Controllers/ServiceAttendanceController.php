<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Events\Models\Event;
use App\Domains\Meetings\Models\Meeting;
use App\Domains\Members\Models\Member;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectActivity;
use App\Domains\ServiceDelivery\Models\ServiceAttendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceAttendanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ServiceDelivery/Attendance', [
            'members' => Member::query()->select('id', 'first_name', 'last_name')->orderBy('first_name')->get()->map(fn (Member $member) => [
                'id' => $member->id,
                'name' => trim($member->first_name.' '.$member->last_name),
            ]),
            'beneficiaries' => Beneficiary::query()->select('id', 'name', 'surname')->orderBy('name')->get()->map(fn (Beneficiary $beneficiary) => [
                'id' => $beneficiary->id,
                'name' => trim($beneficiary->name.' '.$beneficiary->surname),
            ]),
            'programs' => Program::query()->select('id', 'title')->orderBy('title')->get(),
            'projects' => Project::query()->select('id', 'name')->orderBy('name')->get(),
            'activities' => ProjectActivity::query()->select('id', 'name')->orderBy('name')->get(),
            'events' => Event::query()->select('id', 'title')->orderBy('title')->get(),
            'meetings' => Meeting::query()->select('id', 'title')->orderBy('title')->get(),
            'attendance' => ServiceAttendance::query()
                ->with(['member:id,first_name,last_name', 'program:id,title', 'project:id,name', 'activity:id,name'])
                ->latest('attendance_date')
                ->latest('id')
                ->get()
                ->map(fn (ServiceAttendance $record) => [
                    'id' => $record->id,
                    'member_id' => $record->member_id,
                    'member_name' => $record->member ? trim($record->member->first_name.' '.$record->member->last_name) : null,
                    'beneficiary_id' => $record->beneficiary_id,
                    'program_id' => $record->program_id,
                    'program_title' => $record->program?->title,
                    'project_id' => $record->project_id,
                    'project_name' => $record->project?->name,
                    'project_activity_id' => $record->project_activity_id,
                    'project_activity_name' => $record->activity?->name,
                    'attendance_type' => $record->attendance_type,
                    'attendance_date' => $record->attendance_date?->format('Y-m-d'),
                    'attendance_status' => $record->attendance_status,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ServiceAttendance::query()->create($request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'beneficiary_id' => ['nullable', 'integer', 'exists:beneficiaries,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'project_activity_id' => ['nullable', 'integer', 'exists:project_activities,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'meeting_id' => ['nullable', 'integer', 'exists:meetings,id'],
            'attendance_type' => ['required', 'in:workshop,training,event,meeting'],
            'attendance_date' => ['required', 'date'],
            'attendance_status' => ['required', 'string', 'max:100'],
        ]));

        return redirect()->back()->with('success', 'Attendance record saved.');
    }

    public function update(Request $request, ServiceAttendance $attendance): RedirectResponse
    {
        $attendance->update($request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'beneficiary_id' => ['nullable', 'integer', 'exists:beneficiaries,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'project_activity_id' => ['nullable', 'integer', 'exists:project_activities,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'meeting_id' => ['nullable', 'integer', 'exists:meetings,id'],
            'attendance_type' => ['required', 'in:workshop,training,event,meeting'],
            'attendance_date' => ['required', 'date'],
            'attendance_status' => ['required', 'string', 'max:100'],
        ]));

        return redirect()->back()->with('success', 'Attendance record updated.');
    }
}
