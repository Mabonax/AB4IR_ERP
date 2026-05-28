<?php

use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Models\AssetAssignment;
use App\Domains\Assets\Models\AssetCategory;
use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function makeDashboardDepartment(string $name): StaffDepartment
{
    return StaffDepartment::query()->create([
        'name' => $name,
        'description' => $name.' department',
    ]);
}

function makeDashboardUser(StaffDepartment $department, string $email, array $permissions = [], ?StaffMember $manager = null, bool $isManager = false): array
{
    $user = User::factory()->create([
        'email' => $email,
        'name' => strtok($email, '@'),
    ]);

    $staff = StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'manager_id' => $manager?->id,
        'first_name' => ucfirst(strtok($email, '.')),
        'last_name' => 'Dashboard',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => $isManager,
        'is_ceo' => false,
    ]);

    $user->staffMember()->save($staff);

    if ($permissions !== []) {
        grantPermissions($user, $permissions);
    }

    return [$user->refresh(), $staff->refresh()];
}

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.tasks.available', false)
            ->where('dashboard.tickets.summary.total', 0)
        );
});

test('task-enabled users receive a task-first dashboard payload', function () {
    $operations = makeDashboardDepartment('Operations');
    $technical = makeDashboardDepartment('Technical');
    [$manager] = makeDashboardUser($operations, 'dash.manager@example.test', ['domain.task-management.view', 'domain.task-management.manage'], isManager: true);
    [$report] = makeDashboardUser($operations, 'dash.report@example.test');

    $task = WorkTask::query()->create([
        'title' => 'Prepare monthly board pack',
        'status' => 'open',
        'priority' => 'high',
        'due_date' => now()->subDay()->toDateString(),
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $manager->id,
        'assigned_department_id' => $operations->id,
    ]);

    WorkTask::query()->create([
        'title' => 'Operations queue intake',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $report->id,
        'creator_department_id' => $operations->id,
        'assigned_department_id' => $operations->id,
    ]);

    $ticket = SupportTicket::query()->create([
        'title' => 'Printer is offline',
        'description' => 'Urgent support needed.',
        'status' => 'assigned',
        'priority' => 'high',
        'requester_user_id' => $manager->id,
        'requester_department_id' => $operations->id,
        'assigned_to_user_id' => $manager->id,
        'assigned_department_id' => $technical->id,
    ]);
    $ticket->forceFill([
        'created_at' => now()->subHours(8),
        'updated_at' => now()->subHours(8),
    ])->save();

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.tasks.available', true)
            ->where('dashboard.tasks.summary.assigned_to_me', 1)
            ->where('dashboard.tasks.summary.overdue', 1)
            ->where('dashboard.tasks.assigned.0.id', $task->id)
            ->where('dashboard.tasks.overdue.0.id', $task->id)
            ->where('dashboard.tasks.queue.0.title', 'Operations queue intake')
            ->where('dashboard.tickets.summary.assigned_to_me', 1)
            ->where('dashboard.tickets.assigned.0.id', $ticket->id)
        );
});

test('ordinary staff dashboard stays focused on personal task and requester workflow', function () {
    $operations = makeDashboardDepartment('Operations');
    [$manager, $managerStaff] = makeDashboardUser($operations, 'staff.manager@example.test', ['domain.task-management.view', 'domain.task-management.manage'], isManager: true);
    [$staffUser] = makeDashboardUser($operations, 'staff.member@example.test', ['domain.task-management.view'], manager: $managerStaff);

    $assignedTask = WorkTask::query()->create([
        'title' => 'Personal task only',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $staffUser->id,
        'assigned_department_id' => $operations->id,
    ]);

    SupportTicket::query()->create([
        'title' => 'My printer is failing',
        'description' => 'Requester-visible ticket.',
        'status' => 'open',
        'priority' => 'medium',
        'requester_user_id' => $staffUser->id,
        'requester_department_id' => $operations->id,
    ]);

    $this->actingAs($staffUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.tasks.persona', 'staff')
            ->where('dashboard.tasks.can_create', false)
            ->where('dashboard.tasks.summary.total', 1)
            ->where('dashboard.tasks.assigned.0.id', $assignedTask->id)
            ->where('dashboard.tasks.queue', [])
            ->where('dashboard.tickets.can_respond', false)
            ->where('dashboard.tickets.summary.requested_by_me', 1)
        );
});

test('technical responder dashboard does not expose unassigned support queue items without manager authority', function () {
    $operations = makeDashboardDepartment('Operations');
    $technical = makeDashboardDepartment('Technical');
    [$requester] = makeDashboardUser($operations, 'incident.requester@example.test');
    [$responder] = makeDashboardUser($technical, 'incident.responder@example.test', ['technical-tickets.respond']);

    $ticket = SupportTicket::query()->create([
        'title' => 'Network outage',
        'description' => 'Responder workflow item.',
        'status' => 'open',
        'priority' => 'urgent',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);
    $ticket->forceFill([
        'created_at' => now()->subHours(9),
        'updated_at' => now()->subHours(9),
    ])->save();

    $this->actingAs($responder)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.tasks.persona', 'technical_responder')
            ->where('dashboard.tickets.persona', 'technical_responder')
            ->where('dashboard.tickets.can_respond', true)
            ->where('dashboard.tickets.summary.total', 0)
            ->where('dashboard.tickets.summary.unassigned_queue', 0)
            ->where('dashboard.tickets.unassigned', [])
            ->where('dashboard.tickets.overdue', [])
        );
});

test('leave approvers receive secondary workflow widgets even without task access', function () {
    $operations = makeDashboardDepartment('Operations');
    [$managerUser, $managerStaff] = makeDashboardUser($operations, 'leave.manager@example.test', ['domain.leave.manage', 'domain.human-resources.view']);
    [, $staff] = makeDashboardUser($operations, 'leave.staff@example.test', ['domain.leave.view'], manager: $managerStaff);

    LeaveRequest::query()->create([
        'staff_member_id' => $staff->id,
        'manager_id' => $managerStaff->id,
        'leave_type' => 'annual',
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeek()->toDateString(),
        'total_days' => 1,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($managerUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.tasks.available', false)
            ->where('dashboard.secondary.0.key', 'leave')
            ->where('dashboard.secondary.0.value', 1)
        );
});

test('asset users receive an asset portfolio widget on the home dashboard', function () {
    $technical = makeDashboardDepartment('Technical');
    [$assetUser, $assetStaff] = makeDashboardUser($technical, 'assets.dashboard@example.test', ['domain.assets.view']);

    $category = AssetCategory::query()->create([
        'name' => 'Laptops',
        'description' => 'Portable devices',
    ]);

    $asset = Asset::query()->create([
        'asset_category_id' => $category->id,
        'staff_member_id' => $assetStaff->id,
        'name' => 'Field laptop',
        'type' => 'Laptop',
        'model_name' => 'Latitude',
        'asset_code' => 'AST-000001',
        'serial_state' => 'recorded',
        'serial_number' => 'SN-000001',
        'status' => 'assigned',
    ]);

    AssetAssignment::query()->create([
        'asset_id' => $asset->id,
        'department_id' => $technical->id,
        'staff_member_id' => $assetStaff->id,
        'assigned_by' => $assetUser->id,
        'assigned_at' => now()->subDay(),
    ]);

    $this->actingAs($assetUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.secondary.0.key', 'assets')
            ->where('dashboard.secondary.0.value', 1)
        );
});

test('marketing users receive a marketing approvals widget on the home dashboard', function () {
    $marketing = makeDashboardDepartment('Marketing');
    [$manager, $managerStaff] = makeDashboardUser($marketing, 'marketing.dashboard@example.test', ['domain.marketing.view', 'domain.marketing.manage'], isManager: true);
    [$designer] = makeDashboardUser($marketing, 'marketing.designer.dashboard@example.test', ['domain.marketing.view'], manager: $managerStaff);

    MarketingJob::query()->create([
        'title' => 'Campaign banner set',
        'job_type' => 'graphic_design',
        'status' => 'pending_approval',
        'priority' => 'high',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $designer->id,
        'assigned_department_id' => $marketing->id,
        'submitted_for_approval_at' => now(),
        'submitted_by_user_id' => $designer->id,
    ]);

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('dashboard.secondary.0.key', 'marketing')
            ->where('dashboard.secondary.0.value', 1)
        );
});
