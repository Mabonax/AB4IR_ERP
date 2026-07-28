<?php

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Notifications\LeaveRequestNotification;
use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-05-31 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function makeLeaveDepartment(string $name = 'Operations'): StaffDepartment
{
    return StaffDepartment::query()->create([
        'name' => $name,
        'description' => $name.' department',
    ]);
}

function makeLeaveStaffUser(
    StaffDepartment $department,
    string $email,
    ?StaffMember $manager = null,
    array $staffOverrides = [],
    array $permissions = []
): array {
    $user = User::factory()->create([
        'email' => $email,
        'name' => strtok($email, '@'),
    ]);

    $staff = StaffMember::query()->create(array_merge([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'manager_id' => $manager?->id,
        'first_name' => ucfirst(strtok($email, '.')),
        'last_name' => 'Staff',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => '2026-03-01',
        'status' => 'active',
        'is_manager' => false,
    ], $staffOverrides));

    if ($permissions !== []) {
        grantPermissions($user, $permissions);
    }

    return [$user, $staff];
}

test('staff can submit annual leave and working days are calculated correctly', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser(
        $department,
        'manager.leave@example.test',
        permissions: ['domain.leave.manage']
    );

    [$staffUser, $staff] = makeLeaveStaffUser(
        $department,
        'staff.leave@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    Notification::fake();

    $this->actingAs($staffUser)
        ->post('/leave-requests', [
            'leave_type' => 'annual',
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-18',
            'reason' => 'Family matter',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $leave = LeaveRequest::query()->first();

    expect($leave)->not->toBeNull()
        ->and($leave->leave_type)->toBe('annual')
        ->and((float) $leave->total_days)->toBe(2.0);

    Notification::assertSentTo($managerUser, LeaveRequestNotification::class);
});

test('staff can submit sick leave', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser(
        $department,
        'manager.sick@example.test',
        permissions: ['domain.leave.manage']
    );

    [$staffUser] = makeLeaveStaffUser(
        $department,
        'staff.sick@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)
        ->post('/leave-requests', [
            'leave_type' => 'sick',
            'start_date' => '2026-05-19',
            'end_date' => '2026-05-20',
            'reason' => 'Medical rest',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('leave_requests', [
        'leave_type' => 'sick',
        'status' => 'submitted',
    ]);
});

test('annual leave is blocked when annual available balance is insufficient', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.balance@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser($department, 'staff.balance@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    $this->actingAs($staffUser)
        ->post('/leave-requests', [
            'leave_type' => 'annual',
            'start_date' => '2026-05-18',
            'end_date' => '2026-05-22',
            'reason' => 'Extended annual leave',
        ])
        ->assertSessionHasErrors(['end_date']);

    expect(LeaveRequest::query()->count())->toBe(0);
});

test('sick leave is blocked when sick available balance is insufficient', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.sick-limit@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser($department, 'staff.sick-limit@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    $this->actingAs($staffUser)
        ->post('/leave-requests', [
            'leave_type' => 'sick',
            'start_date' => '2026-05-18',
            'end_date' => '2026-06-01',
            'reason' => 'Long recovery',
        ])
        ->assertSessionHasErrors(['end_date']);

    expect(LeaveRequest::query()->count())->toBe(0);
});

test('duplicate leave request for the exact same period is blocked', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.duplicate@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser($department, 'staff.duplicate@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    $payload = [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-20',
        'reason' => 'Family leave',
    ];

    $this->actingAs($staffUser)
        ->post('/leave-requests', $payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($staffUser)
        ->post('/leave-requests', $payload)
        ->assertSessionHasErrors(['start_date']);

    expect(LeaveRequest::query()->count())->toBe(1);
});

test('overlapping leave request period is blocked', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.overlap@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser($department, 'staff.overlap@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    $this->actingAs($staffUser)
        ->post('/leave-requests', [
            'leave_type' => 'sick',
            'start_date' => '2026-05-19',
            'end_date' => '2026-05-21',
            'reason' => 'Primary leave window',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($staffUser)
        ->post('/leave-requests', [
            'leave_type' => 'sick',
            'start_date' => '2026-05-21',
            'end_date' => '2026-05-22',
            'reason' => 'Overlapping request',
        ])
        ->assertSessionHasErrors(['start_date']);

    expect(LeaveRequest::query()->count())->toBe(1);
});

test('revoked or rejected leave periods can be resubmitted', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser($department, 'manager.resubmit@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser($department, 'staff.resubmit@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    $payload = [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-20',
        'reason' => 'Initial request',
    ];

    $this->actingAs($staffUser)
        ->post('/leave-requests', $payload)
        ->assertSessionHasNoErrors();

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($managerUser)
        ->post("/leave-requests/{$leave->id}/manager-reject", ['manager_comment' => 'Try again later'])
        ->assertSessionHasNoErrors();

    $this->actingAs($staffUser)
        ->post('/leave-requests', $payload)
        ->assertSessionHasNoErrors();

    expect(LeaveRequest::query()->count())->toBe(2);
});

test('assigned manager can approve leave and balances change only after hr approval', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser($department, 'manager.approve@example.test', permissions: ['domain.leave.manage']);
    [$hrUser] = makeLeaveStaffUser($department, 'hr.approve@example.test', permissions: ['domain.leave.manage', 'domain.human-resources.manage']);
    [$staffUser, $staff] = makeLeaveStaffUser($department, 'staff.approve@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    Notification::fake();

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-20',
        'reason' => 'Family leave',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();
    $service = app(LeaveManagementService::class);

    $before = $service->summarizeStaff($staff);
    expect($before['annual']['taken'])->toBe(0.0);

    $this->actingAs($managerUser)
        ->post("/leave-requests/{$leave->id}/manager-approve", ['manager_comment' => 'Approved'])
        ->assertSessionHasNoErrors();

    $middle = $service->summarizeStaff($staff);
    expect($middle['annual']['taken'])->toBe(0.0)
        ->and($leave->fresh()->status)->toBe('manager_approved');

    $this->actingAs($hrUser)
        ->post("/leave-requests/{$leave->id}/hr-approve", ['hr_comment' => 'Confirmed'])
        ->assertSessionHasNoErrors();

    $after = $service->summarizeStaff($staff);
    expect($after['annual']['taken'])->toBe(2.0)
        ->and($after['annual']['available'])->toBeLessThan($before['annual']['available']);

    Notification::assertSentTo($staffUser, LeaveRequestNotification::class, 2);
    Notification::assertSentTo($hrUser, LeaveRequestNotification::class);
});

test('wrong manager cannot approve another managers leave request', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.owner@example.test', permissions: ['domain.leave.manage']);
    [$wrongManagerUser] = makeLeaveStaffUser($department, 'manager.wrong@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser($department, 'staff.owner@example.test', manager: $managerStaff, permissions: ['domain.leave.view']);

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-19',
        'reason' => 'Personal day',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($wrongManagerUser)
        ->post("/leave-requests/{$leave->id}/manager-approve", ['manager_comment' => 'No'])
        ->assertSessionHasErrors(['authorization']);

    expect($leave->fresh()->status)->toBe('submitted');
});

test('requester can view their leave request detail page', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.show@example.test', permissions: ['domain.leave.manage']);
    [$staffUser, $staff] = makeLeaveStaffUser(
        $department,
        'staff.show@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-20',
        'reason' => 'Family leave',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($staffUser)
        ->get("/leave-requests/{$leave->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LeaveRequests/Show')
            ->where('leaveRequest.staff_member.name', trim($staff->first_name.' '.$staff->last_name))
            ->where('leaveRequest.requested_period.total_days', 2)
            ->where('leaveRequest.permissions.is_requester', true)
            ->where('leaveRequest.permissions.can_revoke', true)
            ->has('leaveRequest.timeline', 1)
        );
});

test('manager can view the leave request detail page for their direct report', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser($department, 'manager.detail@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser(
        $department,
        'staff.detail@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-19',
        'reason' => 'Personal day',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($managerUser)
        ->get("/leave-requests/{$leave->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LeaveRequests/Show')
            ->where('leaveRequest.permissions.is_manager_user', true)
            ->where('leaveRequest.permissions.can_manager_approve', true)
            ->where('leaveRequest.permissions.can_hr_approve', false)
        );
});

test('hr can view the leave request detail page for hr approval stage', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser($department, 'manager.hrshow@example.test', permissions: ['domain.leave.manage']);
    [$hrUser] = makeLeaveStaffUser($department, 'hr.hrshow@example.test', permissions: ['domain.human-resources.manage']);
    [$staffUser] = makeLeaveStaffUser(
        $department,
        'staff.hrshow@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-20',
        'reason' => 'Family leave',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($managerUser)->post("/leave-requests/{$leave->id}/manager-approve", [
        'manager_comment' => 'Approved',
    ]);

    $this->actingAs($hrUser)
        ->get("/leave-requests/{$leave->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('LeaveRequests/Show')
            ->where('leaveRequest.permissions.is_hr_user', true)
            ->where('leaveRequest.permissions.can_hr_approve', true)
            ->where('leaveRequest.manager_comment', 'Approved')
            ->has('leaveRequest.timeline', 2)
        );
});

test('unrelated staff member cannot view another pending leave request detail page', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.private@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser(
        $department,
        'staff.private@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );
    [$outsiderUser] = makeLeaveStaffUser(
        $department,
        'outsider.private@example.test',
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-19',
        'reason' => 'Private leave',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($outsiderUser)
        ->get("/leave-requests/{$leave->id}")
        ->assertForbidden();
});

test('settings leave page receives annual and sick summaries', function () {
    $department = makeLeaveDepartment();
    [, $managerStaff] = makeLeaveStaffUser($department, 'manager.settings@example.test', permissions: ['domain.leave.manage']);
    [$staffUser] = makeLeaveStaffUser(
        $department,
        'staff.settings@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)
        ->get('/settings/leave')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/leave')
            ->where('leaveAccount.annual.accrued', 2.5)
            ->where('leaveAccount.sick.entitlement', 10)
            ->has('leaveTypes', 2)
        );
});

test('staff profile exposes leave account data', function () {
    $department = makeLeaveDepartment();
    [$viewer] = makeLeaveStaffUser($department, 'viewer.profile@example.test', permissions: ['domain.staff.view']);
    $staff = StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Mpho',
        'last_name' => 'Profile',
        'email' => 'mpho.profile@example.test',
        'phone' => '0711112222',
        'employee_number' => 'PROF-001',
        'start_date' => '2026-03-01',
        'status' => 'active',
    ]);

    $this->actingAs($viewer)
        ->get("/staff/{$staff->id}/profile")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Staff/Profile')
            ->where('staff.leave_account.sick.entitlement', 10)
            ->where('staff.leave_account.annual.available', 2.5)
        );
});

test('hr dashboard receives leave summary register', function () {
    $department = makeLeaveDepartment();
    [$hrUser] = makeLeaveStaffUser($department, 'hr.dashboard@example.test', permissions: ['domain.human-resources.manage', 'domain.staff.manage']);
    StaffMember::query()->create([
        'department_id' => $department->id,
        'first_name' => 'Lindi',
        'last_name' => 'Employee',
        'email' => 'lindi.employee@example.test',
        'phone' => '0713334444',
        'employee_number' => 'HRD-001',
        'start_date' => '2026-03-01',
        'status' => 'active',
    ]);

    $this->actingAs($hrUser)
        ->get('/human-resources')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanResources/Dashboard')
            ->has('leaveSummary.staff', 2)
            ->where('leaveSummary.totals.sick_available', 20)
        );
});

test('staff can revoke a pending leave request and manager is notified', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser(
        $department,
        'manager.revoke@example.test',
        permissions: ['domain.leave.manage', 'domain.human-resources.view']
    );
    [$staffUser] = makeLeaveStaffUser(
        $department,
        'staff.revoke@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    Notification::fake();

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-21',
        'end_date' => '2026-05-22',
        'reason' => 'Travel',
    ]);

    $leave = LeaveRequest::query()->firstOrFail();

    $this->actingAs($staffUser)
        ->post("/leave-requests/{$leave->id}/revoke")
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Leave request revoked');

    expect($leave->fresh()->status)->toBe('cancelled');

    Notification::assertSentTo($managerUser, LeaveRequestNotification::class, 2);
});

test('manager dashboard shows actionable pending leave approvals for direct reports', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser(
        $department,
        'manager.board@example.test',
        permissions: ['domain.leave.manage', 'domain.human-resources.view']
    );
    [$staffUser, $staff] = makeLeaveStaffUser(
        $department,
        'staff.board@example.test',
        manager: $managerStaff,
        permissions: ['domain.leave.view']
    );

    $this->actingAs($staffUser)->post('/leave-requests', [
        'leave_type' => 'annual',
        'start_date' => '2026-05-19',
        'end_date' => '2026-05-19',
        'reason' => 'Family event',
    ]);

    $this->actingAs($managerUser)
        ->get('/human-resources')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HumanResources/Dashboard')
            ->where('canManageManagerLeave', true)
            ->where('canManageHrLeave', false)
            ->has('pendingLeaveApprovals', 1)
            ->where('pendingLeaveApprovals.0.staff_member_name', trim($staff->first_name.' '.$staff->last_name))
        );
});

test('manager facing data includes only direct reports', function () {
    $department = makeLeaveDepartment();
    [$managerUser, $managerStaff] = makeLeaveStaffUser($department, 'manager.dashboard@example.test', permissions: ['domain.staff.view']);
    makeLeaveStaffUser($department, 'report.one@example.test', manager: $managerStaff);
    makeLeaveStaffUser($department, 'report.two@example.test', manager: $managerStaff);
    makeLeaveStaffUser($department, 'outsider@example.test');

    $this->actingAs($managerUser)
        ->get('/staff/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Staff/Dashboard')
            ->where('managerLeave.team_members', 2)
            ->has('managerLeave.team', 2)
        );
});
