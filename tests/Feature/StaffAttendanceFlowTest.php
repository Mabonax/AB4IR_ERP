<?php

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceActivity;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeAttendanceUser(string $email, array $staffAttributes = [], ?StaffMember $manager = null): array
{
    $department = StaffDepartment::query()->first() ?? StaffDepartment::query()->create([
        'name' => 'Operations',
        'description' => 'Operations',
    ]);

    $user = User::factory()->create([
        'email' => $email,
        'name' => 'Attendance User',
    ]);

    $staff = StaffMember::query()->create(array_merge([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'manager_id' => $manager?->id,
        'first_name' => 'Attendance',
        'last_name' => 'User',
        'email' => $email,
        'employee_number' => 'ATT-'.random_int(100, 999),
        'start_date' => '2026-06-01',
        'status' => 'active',
    ], $staffAttributes));

    $user->forceFill(['staff_id' => $staff->id])->save();

    return [$user->refresh(), $staff->refresh()];
}

beforeEach(function () {
    config()->set('staff_attendance.timezone', 'Africa/Johannesburg');
    config()->set('staff_attendance.clock_in_cutoff', '09:00');
    config()->set('staff_attendance.auto_clock_out_time', '16:30');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('staff can clock in, clock out, and view their records', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 08:30:00', 'Africa/Johannesburg'));

    [$user, $staff] = makeAttendanceUser('attendance.self@example.test');

    $this->actingAs($user)
        ->post('/settings/attendance/clock-in')
        ->assertSessionHas('success', 'Clock-in recorded successfully.');

    Carbon::setTestNow(Carbon::parse('2026-06-08 16:45:00', 'Africa/Johannesburg'));

    $this->actingAs($user)
        ->post('/settings/attendance/clock-out')
        ->assertSessionHas('success', 'Clock-out recorded successfully.');

    $record = StaffAttendanceRecord::query()->where('staff_member_id', $staff->id)->firstOrFail();

    expect($record->clock_in_status)->toBe('on_time')
        ->and($record->clock_out_source)->toBe('self');

    $this->actingAs($user)
        ->get('/settings/attendance')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/attendance')
            ->where('staff.id', $staff->id)
            ->where('today.record.clock_in_status_label', 'On time')
            ->has('history', 1)
        );
});

test('staff cannot clock in after nine without a manager override', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 09:30:00', 'Africa/Johannesburg'));

    [$user] = makeAttendanceUser('attendance.late@example.test');

    $this->actingAs($user)
        ->post('/settings/attendance/clock-in')
        ->assertSessionHasErrors('attendance');

    expect(StaffAttendanceRecord::query()->count())->toBe(0);
});

test('staff can submit a late clock in reason and line manager can approve it before clock in', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 09:30:00', 'Africa/Johannesburg'));

    [$managerUser, $managerStaff] = makeAttendanceUser('attendance.manager@example.test', [
        'first_name' => 'Maya',
        'last_name' => 'Manager',
        'is_manager' => true,
    ]);
    grantPermissions($managerUser, ['domain.leave.manage', 'domain.staff.view']);

    [$staffUser, $staff] = makeAttendanceUser('attendance.report@example.test', [
        'first_name' => 'Rita',
        'last_name' => 'Report',
    ], $managerStaff);

    $this->actingAs($staffUser)
        ->post('/settings/attendance/late-request', [
            'reason' => 'Traffic accident blocked the main route.',
        ])
        ->assertSessionHas('success', 'Late clock-in request sent to your line manager.');

    $this->actingAs($managerUser)
        ->post('/human-resources/attendance/late-overrides', [
            'staff_id' => $staff->id,
            'reason' => 'Manager reviewed the delay and approved the late arrival.',
        ])
        ->assertSessionHas('success', 'Late clock-in request approved successfully.');

    $this->actingAs($staffUser)
        ->post('/settings/attendance/clock-in')
        ->assertSessionHas('success', 'Clock-in recorded successfully.');

    $record = StaffAttendanceRecord::query()->where('staff_member_id', $staff->id)->firstOrFail();

    expect($record->clock_in_status)->toBe('late_override')
        ->and($record->lateOverride)->not->toBeNull()
        ->and($record->lateOverride?->request_reason)->toBe('Traffic accident blocked the main route.')
        ->and($record->lateOverride?->reason)->toBe('Manager reviewed the delay and approved the late arrival.');

    $this->actingAs($managerUser)
        ->get('/human-resources/attendance')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanResources/Attendance')
            ->where('reportRows.0.staff_name', 'Rita Report')
            ->where('pendingRequests', [])
            ->where('recentActivities.0.action_label', 'Clock In')
        );

    expect(StaffAttendanceActivity::query()->where('action', 'late_request_submitted')->count())->toBe(1)
        ->and(StaffAttendanceActivity::query()->where('action', 'late_request_approved')->count())->toBe(1);
});

test('late request stays pending until the manager approves it', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 09:30:00', 'Africa/Johannesburg'));

    [$managerUser, $managerStaff] = makeAttendanceUser('attendance.pending.manager@example.test', [
        'first_name' => 'Maya',
        'last_name' => 'Manager',
        'is_manager' => true,
    ]);
    grantPermissions($managerUser, ['domain.leave.manage', 'domain.staff.view']);

    [$staffUser] = makeAttendanceUser('attendance.pending.staff@example.test', [
        'first_name' => 'Peta',
        'last_name' => 'Pending',
    ], $managerStaff);

    $this->actingAs($staffUser)
        ->post('/settings/attendance/late-request', [
            'reason' => 'School drop-off emergency.',
        ])
        ->assertSessionHas('success', 'Late clock-in request sent to your line manager.');

    $this->actingAs($staffUser)
        ->get('/settings/attendance')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/attendance')
            ->where('today.pending_request.request_reason', 'School drop-off emergency.')
            ->where('today.can_clock_in', false)
        );

    $this->actingAs($managerUser)
        ->get('/human-resources/attendance')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanResources/Attendance')
            ->where('pendingRequests.0.staff_member_name', 'Peta Pending')
            ->where('pendingRequests.0.request_reason', 'School drop-off emergency.')
        );
});

test('auto clock out command closes open staff attendance at four thirty pm', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 08:15:00', 'Africa/Johannesburg'));

    [$user, $staff] = makeAttendanceUser('attendance.auto@example.test');

    $this->actingAs($user)
        ->post('/settings/attendance/clock-in')
        ->assertSessionHas('success', 'Clock-in recorded successfully.');

    Carbon::setTestNow(Carbon::parse('2026-06-08 16:30:00', 'Africa/Johannesburg'));

    $this->artisan('staff-attendance:auto-clock-out')
        ->assertSuccessful();

    $record = StaffAttendanceRecord::query()->where('staff_member_id', $staff->id)->firstOrFail();

    expect($record->clock_out_source)->toBe('auto')
        ->and($record->clock_out_at?->format('H:i:s'))->toBe('16:30:00');
});

test('scheduled prompt clock out records the default four thirty pm time', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 08:15:00', 'Africa/Johannesburg'));

    [$user, $staff] = makeAttendanceUser('attendance.promptclockout@example.test');

    $this->actingAs($user)
        ->post('/settings/attendance/clock-in')
        ->assertSessionHas('success', 'Clock-in recorded successfully.');

    Carbon::setTestNow(Carbon::parse('2026-06-08 16:26:00', 'Africa/Johannesburg'));

    $this->actingAs($user)
        ->post('/settings/attendance/clock-out', [
            'use_default_time' => true,
        ])
        ->assertSessionHas('success', 'Clock-out recorded successfully.');

    $record = StaffAttendanceRecord::query()->where('staff_member_id', $staff->id)->firstOrFail();

    expect($record->clock_out_source)->toBe('scheduled_prompt')
        ->and($record->clock_out_at?->format('H:i:s'))->toBe('16:30:00');
});

test('attendance report pdf is printable for an individual staff member', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 08:00:00', 'Africa/Johannesburg'));

    [$managerUser, $managerStaff] = makeAttendanceUser('attendance.pdfmanager@example.test', [
        'first_name' => 'Paula',
        'last_name' => 'Manager',
        'is_manager' => true,
    ]);
    grantPermissions($managerUser, ['domain.staff.view']);

    [$staffUser, $staff] = makeAttendanceUser('attendance.pdfstaff@example.test', [
        'first_name' => 'Sam',
        'last_name' => 'Staff',
    ], $managerStaff);

    $this->actingAs($staffUser)
        ->post('/settings/attendance/clock-in')
        ->assertSessionHas('success', 'Clock-in recorded successfully.');

    $response = $this->actingAs($managerUser)
        ->get('/human-resources/attendance/report/pdf?period=week&anchor_date=2026-06-08&staff_id='.$staff->id);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
