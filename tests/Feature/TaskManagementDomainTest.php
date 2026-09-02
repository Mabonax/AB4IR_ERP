<?php

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Marketing\Models\MarketingDeliverable;
use App\Domains\Marketing\Models\MarketingRequest;
use App\Domains\TaskManagement\Jobs\SendTaskManagementReminderNotificationsJob;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Notifications\SupportTicketAssignedNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketActivityNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketOverdueNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketResolvedNotification;
use App\Domains\TaskManagement\Notifications\TaskActivityNotification;
use App\Domains\TaskManagement\Notifications\TaskAssignedNotification;
use App\Domains\TaskManagement\Notifications\TaskOverdueReminderNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'domain.task-management.view',
        'domain.task-management.manage',
        'technical-tickets.respond',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

function makeDepartment(string $name): StaffDepartment
{
    return StaffDepartment::query()->create([
        'name' => $name,
        'description' => $name.' department',
    ]);
}

function makeStaffUser(StaffDepartment $department, string $email, bool $asManager = false, ?StaffMember $manager = null): array
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
        'last_name' => 'User',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => $asManager,
        'is_ceo' => false,
    ]);

    $user->staffMember()->save($staff);
    $user->givePermissionTo($asManager ? ['domain.task-management.view', 'domain.task-management.manage'] : ['domain.task-management.view']);

    return [$user, $staff];
}

test('department manager can assign task to a direct report', function () {
    Notification::fake();

    $department = makeDepartment('Marketing');
    [$manager, $managerStaff] = makeStaffUser($department, 'manager@example.test', asManager: true);
    [$report] = makeStaffUser($department, 'report@example.test', manager: $managerStaff);

    $this->actingAs($manager)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Prepare outreach list',
            'description' => 'Compile prospect data',
            'priority' => 'high',
            'due_date' => now()->addDay()->toDateString(),
            'assigned_to_user_id' => $report->id,
            'assigned_department_id' => '',
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    $this->assertDatabaseHas('work_tasks', [
        'title' => 'Prepare outreach list',
        'assigned_to_user_id' => $report->id,
        'creator_user_id' => $manager->id,
    ]);

    Notification::assertSentTo($report, TaskAssignedNotification::class);
    Notification::assertSentTo($manager, TaskAssignedNotification::class);
});

test('department queue tasks notify the responsible department managers instead of blasting all staff', function () {
    Notification::fake();

    $operations = makeDepartment('Operations');
    [$creator] = makeStaffUser($operations, 'queue.creator@example.test', asManager: true);
    [$departmentManager] = makeStaffUser($operations, 'queue.manager.notify@example.test', asManager: true);
    [$staffUser] = makeStaffUser($operations, 'queue.staff.notify@example.test');

    $this->actingAs($creator)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Department intake review',
            'description' => 'Queue-only assignment',
            'priority' => 'medium',
            'due_date' => now()->addDay()->toDateString(),
            'assigned_to_user_id' => '',
            'assigned_department_id' => $operations->id,
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    Notification::assertSentTo($departmentManager, TaskAssignedNotification::class);
    Notification::assertNotSentTo($staffUser, TaskAssignedNotification::class);
});

test('manager cannot assign general task to another department without a managed project', function () {
    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$manager, $managerStaff] = makeStaffUser($marketing, 'marketing.manager@example.test', asManager: true);
    [$technicalUser] = makeStaffUser($technical, 'technical.user@example.test');

    $this->actingAs($manager)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Cross department ask',
            'description' => 'Should be blocked',
            'priority' => 'medium',
            'due_date' => '',
            'assigned_to_user_id' => $technicalUser->id,
            'assigned_department_id' => '',
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertSessionHasErrors(['assigned_to_user_id']);

    expect(WorkTask::query()->count())->toBe(0);
});

test('project manager can assign project task across departments', function () {
    Notification::fake();

    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$manager, $managerStaff] = makeStaffUser($marketing, 'project.manager@example.test', asManager: true);
    [$technicalUser] = makeStaffUser($technical, 'project.tech@example.test');
    $program = Program::query()->create([
        'title' => 'Task Management Program',
        'description' => 'Program for project-linked task testing',
        'slug' => 'task-management-program',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'name' => 'Cross Department Project',
        'project_manager_id' => $managerStaff->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'status' => 'planned',
        'description' => 'Test project',
    ]);

    $this->actingAs($manager)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Configure project devices',
            'description' => 'Technical setup required',
            'priority' => 'urgent',
            'due_date' => now()->addDays(2)->toDateString(),
            'assigned_to_user_id' => $technicalUser->id,
            'assigned_department_id' => $technical->id,
            'project_id' => $project->id,
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    $this->assertDatabaseHas('work_tasks', [
        'title' => 'Configure project devices',
        'project_id' => $project->id,
        'assigned_to_user_id' => $technicalUser->id,
    ]);

    Notification::assertSentTo($technicalUser, TaskAssignedNotification::class);
});

test('non manager cannot create project task even when linked project exists', function () {
    $marketing = makeDepartment('Marketing');
    [$user] = makeStaffUser($marketing, 'project.staff@example.test');
    $user->givePermissionTo(['domain.task-management.manage']);

    $program = Program::query()->create([
        'title' => 'Restricted Program',
        'description' => 'Program for non-manager restriction testing',
        'slug' => 'restricted-program',
    ]);

    $project = Project::query()->create([
        'program_id' => $program->id,
        'name' => 'Restricted Project',
        'project_manager_id' => null,
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'No manager authority should come from permissions alone.',
    ]);

    $this->actingAs($user)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Unauthorized project task',
            'description' => 'This should not be allowed.',
            'priority' => 'medium',
            'due_date' => '',
            'assigned_to_user_id' => $user->id,
            'assigned_department_id' => $marketing->id,
            'project_id' => $project->id,
            'program_id' => '',
        ])
        ->assertForbidden();

    expect(WorkTask::query()->count())->toBe(0);
});

test('requester can log ticket and technical manager can assign and resolve it', function () {
    Notification::fake();

    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'tech.responder@example.test', asManager: true);
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $this->actingAs($requester)
        ->post(route('task-management.tickets.store'), [
            'title' => 'Laptop not connecting to Wi-Fi',
            'description' => 'Cannot access internal systems.',
            'priority' => 'high',
            'support_area' => 'hardware',
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $ticket = SupportTicket::query()->firstOrFail();

    expect($ticket->support_area)->toBe('hardware')
        ->and($ticket->assigned_department_id)->toBe($technical->id);

    $this->actingAs($technicalUser)
        ->post(route('task-management.tickets.assign', $ticket), [
            'assigned_to_user_id' => $technicalUser->id,
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $this->actingAs($technicalUser)
        ->post(route('task-management.tickets.resolve', $ticket), [
            'resolution_summary' => 'Updated the wireless profile and restored connectivity.',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $ticket->refresh();

    expect($ticket->first_responded_at)->not->toBeNull();

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'resolved',
        'assigned_to_user_id' => $technicalUser->id,
    ]);

    $this->assertDatabaseHas('support_ticket_replies', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $technicalUser->id,
        'is_resolution' => true,
    ]);

    Notification::assertSentTo($technicalUser, SupportTicketAssignedNotification::class);
    Notification::assertSentTo($technicalUser, SupportTicketAssignedNotification::class, function ($notification, $channels) use ($ticket) {
        return $notification->toArray($ticket)['ticket_id'] === $ticket->id;
    });
    Notification::assertSentTo($requester, SupportTicketResolvedNotification::class);
});

test('technical responder cannot assign ticket without technical manager authority', function () {
    $operations = makeDepartment('Operations');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($operations, 'queue.requester@example.test');
    [$responder] = makeStaffUser($technical, 'queue.responder@example.test');
    $responder->givePermissionTo(['technical-tickets.respond']);

    $ticket = SupportTicket::query()->create([
        'title' => 'Email client broken',
        'description' => 'Responder should not govern queue assignment.',
        'status' => 'open',
        'priority' => 'medium',
        'support_area' => 'software',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($responder)
        ->post(route('task-management.tickets.assign', $ticket), [
            'assigned_to_user_id' => $responder->id,
        ])
        ->assertForbidden();
});

test('technical manager can assign reassign and complete technical tickets within the department', function () {
    Notification::fake();

    $operations = makeDepartment('Operations');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($operations, 'tech-manager.requester@example.test');
    [$technicalManager] = makeStaffUser($technical, 'tech.manager@example.test', asManager: true);
    [$technicianA] = makeStaffUser($technical, 'tech.agent.a@example.test');
    [$technicianB] = makeStaffUser($technical, 'tech.agent.b@example.test');

    $technicalManager->givePermissionTo(['technical-tickets.respond']);
    $technicianA->givePermissionTo(['technical-tickets.respond']);
    $technicianB->givePermissionTo(['technical-tickets.respond']);

    $ticket = SupportTicket::query()->create([
        'title' => 'Accounting workstation blue screen',
        'description' => 'Requires technical queue governance.',
        'status' => 'open',
        'priority' => 'high',
        'support_area' => 'hardware',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($technicalManager)
        ->post(route('task-management.tickets.assign', $ticket), [
            'assigned_to_user_id' => $technicianA->id,
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $ticket->refresh();
    expect($ticket->assigned_to_user_id)->toBe($technicianA->id)
        ->and($ticket->status)->toBe('assigned');

    $this->actingAs($technicalManager)
        ->post(route('task-management.tickets.assign', $ticket), [
            'assigned_to_user_id' => $technicianB->id,
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $ticket->refresh();
    expect($ticket->assigned_to_user_id)->toBe($technicianB->id)
        ->and($ticket->status)->toBe('assigned');

    $selfHandled = SupportTicket::query()->create([
        'title' => 'Payroll application license fault',
        'description' => 'Technical manager can complete directly.',
        'status' => 'open',
        'priority' => 'urgent',
        'support_area' => 'software',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($technicalManager)
        ->post(route('task-management.tickets.resolve', $selfHandled), [
            'resolution_summary' => 'License profile refreshed and service restored.',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $selfHandled->refresh();

    expect($selfHandled->assigned_to_user_id)->toBe($technicalManager->id)
        ->and($selfHandled->status)->toBe('resolved')
        ->and($selfHandled->support_area)->toBe('software');
});

test('non manager with task-management manage permission still cannot create tasks', function () {
    $operations = makeDepartment('Operations');
    [$user] = makeStaffUser($operations, 'individual.contributor@example.test');
    $user->givePermissionTo(['domain.task-management.manage']);

    $this->actingAs($user)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Unauthorized task',
            'description' => 'This should not be allowed.',
            'priority' => 'medium',
            'due_date' => '',
            'assigned_to_user_id' => $user->id,
            'assigned_department_id' => $operations->id,
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertForbidden();

    expect(WorkTask::query()->count())->toBe(0);
});

test('department users can view department tasks while cross department tasks remain isolated', function () {
    $operations = makeDepartment('Operations');
    $finance = makeDepartment('Finance');
    [$manager, $managerStaff] = makeStaffUser($operations, 'queue.manager@example.test', asManager: true);
    [$user] = makeStaffUser($operations, 'queue.user@example.test', manager: $managerStaff);
    [$financeUser] = makeStaffUser($finance, 'queue.finance@example.test');

    $assignedTask = WorkTask::query()->create([
        'title' => 'Assigned directly',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $user->id,
        'assigned_department_id' => $operations->id,
    ]);

    WorkTask::query()->create([
        'title' => 'Department queue only',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_department_id' => $operations->id,
    ]);

    $otherDepartmentTask = WorkTask::query()->create([
        'title' => 'Finance only task',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $financeUser->id,
        'creator_department_id' => $finance->id,
        'assigned_department_id' => $finance->id,
    ]);

    $this->actingAs($user)
        ->get(route('task-management.tasks.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->where('can.create', false)
            ->has('tasks.data', 2)
            ->where('tasks.data.0.id', $assignedTask->id)
            ->where('summary.total', 2)
        );

    $this->actingAs($user)
        ->get(route('task-management.tasks.show', $assignedTask))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('task-management.tasks.show', $otherDepartmentTask))
        ->assertForbidden();

    $this->actingAs($financeUser)
        ->get(route('task-management.tasks.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->where('summary.total', 1)
            ->where('tasks.data.0.id', $otherDepartmentTask->id)
        );

    $this->actingAs($manager)
        ->get(route('task-management.tasks.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->where('can.create', true)
            ->has('tasks.data', 2)
            ->where('summary.total', 2)
        );
});

test('plain admin role without technical responder permission cannot assign support tickets', function () {
    $operations = makeDepartment('Operations');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($operations, 'admin-ticket.requester@example.test');
    [$adminUser] = makeStaffUser($technical, 'plain.admin@example.test');

    \Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);
    $adminUser->assignRole('admin');

    $ticket = SupportTicket::query()->create([
        'title' => 'Need technical help',
        'description' => 'Ticket should stay protected from plain admins.',
        'status' => 'open',
        'priority' => 'medium',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($adminUser)
        ->post(route('task-management.tickets.assign', $ticket), [
            'assigned_to_user_id' => $adminUser->id,
        ])
        ->assertForbidden();
});

test('ordinary requester cannot assign or resolve support tickets', function () {
    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'ordinary.requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'assigned.responder@example.test');
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $ticket = SupportTicket::query()->create([
        'title' => 'Access issue',
        'description' => 'This needs responder authority.',
        'status' => 'assigned',
        'priority' => 'high',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $marketing->id,
        'assigned_to_user_id' => $technicalUser->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($requester)
        ->post(route('task-management.tickets.assign', $ticket), [
            'assigned_to_user_id' => $requester->id,
        ])
        ->assertForbidden();

    $this->actingAs($requester)
        ->post(route('task-management.tickets.resolve', $ticket), [
            'resolution_summary' => 'Attempted unauthorized resolution.',
        ])
        ->assertForbidden();
});

test('support ticket index only shows personal tickets for non managers while technical managers retain queue visibility', function () {
    $operations = makeDepartment('Operations');
    $technical = makeDepartment('Technical');
    [$requesterA] = makeStaffUser($operations, 'tickets.requester.a@example.test');
    [$requesterB] = makeStaffUser($operations, 'tickets.requester.b@example.test');
    [$technicalManager] = makeStaffUser($technical, 'tickets.tech.manager@example.test', asManager: true);
    [$technicalResponder] = makeStaffUser($technical, 'tickets.tech.responder@example.test');

    $technicalManager->givePermissionTo(['technical-tickets.respond']);
    $technicalResponder->givePermissionTo(['technical-tickets.respond']);

    $assignedToResponder = SupportTicket::query()->create([
        'title' => 'Responder assigned ticket',
        'description' => 'Visible to assigned responder.',
        'status' => 'assigned',
        'priority' => 'medium',
        'support_area' => 'software',
        'requester_user_id' => $requesterA->id,
        'requester_department_id' => $operations->id,
        'assigned_to_user_id' => $technicalResponder->id,
        'assigned_department_id' => $technical->id,
    ]);

    SupportTicket::query()->create([
        'title' => 'Unrelated queue ticket',
        'description' => 'Should stay hidden from unrelated users.',
        'status' => 'open',
        'priority' => 'high',
        'support_area' => 'hardware',
        'requester_user_id' => $requesterB->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($technicalResponder)
        ->get(route('task-management.tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tickets/Index')
            ->where('can.manageQueue', false)
            ->has('tickets.data', 1)
            ->where('tickets.data.0.id', $assignedToResponder->id)
            ->where('summary.total', 1)
            ->where('technicalResponders', [])
            ->has('requesters', 1)
            ->where('requesters.0.id', $technicalResponder->id)
        );

    $this->actingAs($technicalManager)
        ->get(route('task-management.tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tickets/Index')
            ->where('can.manageQueue', true)
            ->has('tickets.data', 2)
            ->where('summary.total', 2)
        );
});

test('requester can close a resolved ticket and reopen it when the issue returns', function () {
    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'close.requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'close.tech@example.test');
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $ticket = SupportTicket::query()->create([
        'title' => 'Email sync issue',
        'description' => 'Intermittent mailbox sync failure.',
        'status' => 'resolved',
        'priority' => 'medium',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $marketing->id,
        'assigned_to_user_id' => $technicalUser->id,
        'assigned_department_id' => $technical->id,
        'first_responded_at' => now()->subHours(4),
        'resolved_at' => now()->subHour(),
    ]);

    $this->actingAs($requester)
        ->post(route('task-management.tickets.close', $ticket), [
            'closing_notes' => 'Confirmed working again.',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'closed',
        'closed_by_user_id' => $requester->id,
    ]);

    $this->actingAs($requester)
        ->post(route('task-management.tickets.reopen', $ticket), [
            'reason' => 'Issue returned after restart.',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'status' => 'assigned',
        'closed_by_user_id' => null,
    ]);

    $this->assertDatabaseHas('support_ticket_replies', [
        'support_ticket_id' => $ticket->id,
        'user_id' => $requester->id,
        'message' => 'Issue returned after restart.',
    ]);
});

test('manager can reassign task and record workflow history and comments', function () {
    Notification::fake();

    $department = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($department, 'ops.manager@example.test', asManager: true);
    [$reportA] = makeStaffUser($department, 'ops.report.a@example.test', manager: $managerStaff);
    [$reportB] = makeStaffUser($department, 'ops.report.b@example.test', manager: $managerStaff);

    $this->actingAs($manager)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Prepare delivery pack',
            'description' => 'Initial assignment',
            'priority' => 'medium',
            'due_date' => now()->addDay()->toDateString(),
            'assigned_to_user_id' => $reportA->id,
            'assigned_department_id' => '',
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    $task = WorkTask::query()->firstOrFail();

    $this->actingAs($manager)
        ->post(route('task-management.tasks.reassign', $task), [
            'assigned_to_user_id' => $reportB->id,
            'assigned_department_id' => $department->id,
            'reason' => 'Capacity balancing',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->actingAs($reportB)
        ->post(route('task-management.tasks.comment', $task), [
            'message' => 'Received and starting work now.',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->assertDatabaseHas('work_tasks', [
        'id' => $task->id,
        'assigned_to_user_id' => $reportB->id,
        'status' => 'open',
    ]);

    $this->assertDatabaseHas('work_task_comments', [
        'work_task_id' => $task->id,
        'user_id' => $reportB->id,
        'message' => 'Received and starting work now.',
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'reassigned',
    ]);

    Notification::assertSentTo($manager, TaskActivityNotification::class);
    Notification::assertSentTo($reportA, TaskActivityNotification::class);
    Notification::assertSentTo($reportB, TaskAssignedNotification::class);
});

test('assigned task user can update status and comment but cannot reassign the task', function () {
    Notification::fake();

    $operations = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($operations, 'assignee.manager@example.test', asManager: true);
    [$assignee] = makeStaffUser($operations, 'assignee.user@example.test', manager: $managerStaff);
    [$otherAssignee] = makeStaffUser($operations, 'assignee.other@example.test', manager: $managerStaff);

    $this->actingAs($manager)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Follow up with stakeholder',
            'description' => 'Needs assignee collaboration only.',
            'priority' => 'medium',
            'due_date' => now()->addDay()->toDateString(),
            'assigned_to_user_id' => $assignee->id,
            'assigned_department_id' => $operations->id,
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    $task = WorkTask::query()->firstOrFail();

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.status', $task), [
            'status' => 'in_progress',
            'completion_notes' => 'Work started.',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.comment', $task), [
            'message' => 'Waiting on stakeholder response.',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.reassign', $task), [
            'assigned_to_user_id' => $otherAssignee->id,
            'assigned_department_id' => $operations->id,
            'reason' => 'Unauthorized reassignment attempt',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('work_tasks', [
        'id' => $task->id,
        'status' => 'in_progress',
        'assigned_to_user_id' => $assignee->id,
    ]);

    $this->assertDatabaseHas('work_task_comments', [
        'work_task_id' => $task->id,
        'user_id' => $assignee->id,
        'message' => 'Waiting on stakeholder response.',
    ]);

    Notification::assertSentTo($manager, TaskActivityNotification::class);
    Notification::assertSentTo($assignee, TaskActivityNotification::class);
});

test('assigned user can submit proof for review and manager can approve final completion', function () {
    Notification::fake();
    Storage::fake('local');

    $operations = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($operations, 'review.manager@example.test', asManager: true);
    [$assignee] = makeStaffUser($operations, 'review.assignee@example.test', manager: $managerStaff);

    $task = WorkTask::query()->create([
        'title' => 'Compile compliance bundle',
        'status' => 'in_progress',
        'priority' => 'high',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $assignee->id,
        'assigned_department_id' => $operations->id,
    ]);

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.submit-review', $task), [
            'completion_notes' => 'Completed the document pack but forgot the deliverable.',
        ])
        ->assertSessionHasErrors('proof_file');

    $task->refresh();
    expect($task->status)->toBe('in_progress')
        ->and($task->submitted_for_review_at)->toBeNull();

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.submit-review', $task), [
            'completion_notes' => 'Completed the document pack and attached the signed PDF.',
            'proof_url' => 'https://mail.example.test/thread/123',
            'proof_file' => UploadedFile::fake()->create('signed-pack.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $task->refresh();

    expect($task->status)->toBe('pending_review')
        ->and($task->submitted_by_user_id)->toBe($assignee->id)
        ->and($task->submitted_for_review_at)->not->toBeNull()
        ->and($task->proof_file_name)->toBe('signed-pack.pdf')
        ->and($task->closed_at)->toBeNull()
        ->and($task->closed_by_user_id)->toBeNull();

    Storage::disk('local')->assertExists($task->proof_path);
    expect($task->documents()->count())->toBe(0);

    $this->actingAs($manager)
        ->get(route('task-management.tasks.show', $task))
        ->assertInertia(fn (Assert $page) => $page
            ->where('task.proof_file_name', 'signed-pack.pdf')
            ->where('task.proof_mime_type', 'application/pdf')
            ->where('task.can_preview_proof', true)
            ->where('task.proof_download_url', route('task-management.tasks.proof', $task))
            ->where('task.proof_preview_url', route('task-management.tasks.proof.preview', $task))
        );

    $this->actingAs($manager)
        ->get(route('task-management.tasks.proof.preview', $task))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="signed-pack.pdf"');

    $this->actingAs($manager)
        ->post(route('task-management.tasks.finalize', $task), [
            'manager_review_notes' => '',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->assertDatabaseHas('work_tasks', [
        'id' => $task->id,
        'status' => 'completed',
        'reviewed_by_user_id' => $manager->id,
        'manager_review_notes' => null,
        'closed_by_user_id' => $manager->id,
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'submitted_for_review',
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'finalized_completion',
    ]);

    Notification::assertSentTo($manager, TaskActivityNotification::class);
    Notification::assertSentTo($assignee, TaskActivityNotification::class);
});

test('manager can return a submitted task for amendments and assignee cannot self approve it', function () {
    Storage::fake('local');

    $operations = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($operations, 'return.manager@example.test', asManager: true);
    [$assignee] = makeStaffUser($operations, 'return.assignee@example.test', manager: $managerStaff);

    $task = WorkTask::query()->create([
        'title' => 'Submit procurement comparison',
        'status' => 'in_progress',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $assignee->id,
        'assigned_department_id' => $operations->id,
    ]);

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.submit-review', $task), [
            'completion_notes' => 'Uploaded the current comparison sheet.',
            'proof_file' => UploadedFile::fake()->create('comparison.xlsx', 64, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.approve', $task), [
            'manager_review_notes' => 'Unauthorized self approval.',
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->post(route('task-management.tasks.return', $task), [
            'manager_review_notes' => 'Please add the missing vendor quote and resend the evidence.',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $this->assertDatabaseHas('work_tasks', [
        'id' => $task->id,
        'status' => 'changes_requested',
        'reviewed_by_user_id' => $manager->id,
        'manager_review_notes' => 'Please add the missing vendor quote and resend the evidence.',
        'closed_by_user_id' => null,
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'returned_for_amendments',
    ]);
});

test('task transaction stays open until a manager closes it', function () {
    $operations = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($operations, 'transaction.manager@example.test', asManager: true);
    [$assignee, $assigneeStaff] = makeStaffUser($operations, 'transaction.assignee@example.test', manager: $managerStaff);
    [$replacement] = makeStaffUser($operations, 'transaction.replacement@example.test', manager: $managerStaff);

    $this->actingAs($manager)
        ->post(route('task-management.tasks.store'), [
            'title' => 'Prepare signed transaction pack',
            'description' => 'Opened by manager and must stay open until final signoff.',
            'priority' => 'high',
            'due_date' => now()->addDay()->toDateString(),
            'assigned_to_user_id' => $assignee->id,
            'assigned_department_id' => $operations->id,
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    $task = WorkTask::query()->firstOrFail();

    expect($task->closed_at)->toBeNull()
        ->and($task->closed_by_user_id)->toBeNull();

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.status', $task), [
            'status' => 'in_progress',
            'completion_notes' => 'First draft underway.',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $task->refresh();
    expect($task->closed_at)->toBeNull()
        ->and($task->closed_by_user_id)->toBeNull();

    $this->actingAs($manager)
        ->post(route('task-management.tasks.reassign', $task), [
            'assigned_to_user_id' => $replacement->id,
            'assigned_department_id' => $operations->id,
            'reason' => 'Needs another pass before closure.',
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $task->refresh();
    expect($task->closed_at)->toBeNull()
        ->and($task->closed_by_user_id)->toBeNull()
        ->and($task->status)->toBe('open');
});

test('task workflow supports document uploads and downloads for review work', function () {
    Storage::fake('local');

    $operations = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($operations, 'document.manager@example.test', asManager: true);
    [$assignee] = makeStaffUser($operations, 'document.assignee@example.test', manager: $managerStaff);

    $task = WorkTask::query()->create([
        'title' => 'Upload board pack',
        'status' => 'in_progress',
        'priority' => 'high',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $assignee->id,
        'assigned_department_id' => $operations->id,
    ]);

    $this->actingAs($assignee)
        ->post(route('task-management.tasks.documents.store', $task), [
            'title' => 'Board pack draft',
            'document_kind' => 'supporting',
            'notes' => 'Initial editable draft for manager review.',
            'file' => UploadedFile::fake()->create('board-pack.pdf', 96, 'application/pdf'),
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $document = $task->documents()->firstOrFail();
    $originalPath = $document->path;

    Storage::disk('local')->assertExists($document->path);

    $this->actingAs($assignee)
        ->patch(route('task-management.tasks.documents.update', [$task, $document]), [
            'title' => 'Board pack final reference',
            'document_kind' => 'approval_reference',
            'notes' => 'Updated reference pack for manager review.',
            'file' => UploadedFile::fake()->create('board-pack-final.pdf', 128, 'application/pdf'),
        ])
        ->assertRedirect(route('task-management.tasks.show', $task));

    $document->refresh();

    expect($document->title)->toBe('Board pack final reference')
        ->and($document->document_kind)->toBe('approval_reference')
        ->and($document->notes)->toBe('Updated reference pack for manager review.')
        ->and($document->file_name)->toBe('board-pack-final.pdf');

    Storage::disk('local')->assertMissing($originalPath);
    Storage::disk('local')->assertExists($document->path);

    $this->actingAs($manager)
        ->get(route('task-management.tasks.documents.download', [$task, $document]))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('task-management.tasks.documents.preview', [$task, $document]))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="board-pack-final.pdf"');

    $this->actingAs($manager)
        ->get(route('task-management.tasks.show', $task))
        ->assertInertia(fn (Assert $page) => $page
            ->where('task.documents.data.0.file_name', 'board-pack-final.pdf')
            ->where('task.documents.data.0.mime_type', 'application/pdf')
            ->where('task.documents.data.0.can_preview', true)
            ->where('task.documents.data.0.download_url', route('task-management.tasks.documents.download', [$task, $document]))
            ->where('task.documents.data.0.preview_url', route('task-management.tasks.documents.preview', [$task, $document]))
        );

    $this->assertDatabaseHas('work_task_documents', [
        'id' => $document->id,
        'work_task_id' => $task->id,
        'uploaded_by_user_id' => $assignee->id,
        'document_kind' => 'approval_reference',
        'title' => 'Board pack final reference',
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'document_uploaded',
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'document_updated',
    ]);

    $deletedPath = $document->path;

    $this->actingAs($assignee)
        ->delete(route('task-management.tasks.documents.destroy', [$task, $document]))
        ->assertRedirect(route('task-management.tasks.show', $task));

    Storage::disk('local')->assertMissing($deletedPath);

    $this->assertDatabaseMissing('work_task_documents', [
        'id' => $document->id,
    ]);

    $this->assertDatabaseHas('work_task_history', [
        'work_task_id' => $task->id,
        'action' => 'document_deleted',
    ]);
});

test('ticket replies notify the other participants in the workflow', function () {
    Notification::fake();

    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'notify.requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'notify.tech@example.test');
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $ticket = SupportTicket::query()->create([
        'title' => 'VPN issue',
        'description' => 'VPN drops unexpectedly.',
        'status' => 'assigned',
        'priority' => 'medium',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $marketing->id,
        'assigned_to_user_id' => $technicalUser->id,
        'assigned_department_id' => $technical->id,
    ]);

    $this->actingAs($technicalUser)
        ->post(route('task-management.tickets.reply', $ticket), [
            'message' => 'Investigating the VPN profile now.',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    Notification::assertSentTo($requester, SupportTicketActivityNotification::class);
});

test('ticket index exposes overdue filters and task show page exposes full workflow payloads', function () {
    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'dashboard.requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'dashboard.tech@example.test', asManager: true);
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $overdue = SupportTicket::query()->create([
        'title' => 'Critical outage',
        'description' => 'Production users cannot access the portal.',
        'status' => 'open',
        'priority' => 'urgent',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $marketing->id,
        'assigned_department_id' => $technical->id,
    ]);
    $overdue->forceFill([
        'created_at' => now()->subHours(10),
        'updated_at' => now()->subHours(10),
    ])->save();

    $resolved = SupportTicket::query()->create([
        'title' => 'Printer setup',
        'description' => 'Resolved quickly.',
        'status' => 'resolved',
        'priority' => 'low',
        'requester_user_id' => $requester->id,
        'requester_department_id' => $marketing->id,
        'assigned_to_user_id' => $technicalUser->id,
        'assigned_department_id' => $technical->id,
        'first_responded_at' => now()->subHours(2),
        'resolved_at' => now()->subHour(),
    ]);

    $task = WorkTask::query()->create([
        'title' => 'History visible',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $requester->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $requester->id,
        'assigned_department_id' => $marketing->id,
    ]);

    $task->history()->create([
        'actor_user_id' => $requester->id,
        'action' => 'created',
        'summary' => 'Task created for dashboard payload check.',
    ]);

    $task->comments()->create([
        'user_id' => $requester->id,
        'message' => 'Initial note.',
    ]);

    $this->actingAs($technicalUser)
        ->get(route('task-management.tickets.index', ['overdue' => 1]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tickets/Index')
            ->where('summary.overdue', 1)
            ->where('summary.closed', 0)
            ->has('tickets.data', 1)
            ->where('tickets.data.0.id', $overdue->id)
            ->where('tickets.data.0.is_overdue', true)
        );

    $this->actingAs($requester)
        ->get(route('task-management.tasks.show', $task))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Show')
            ->where('task.id', $task->id)
            ->has('task.history.data', 1)
            ->has('task.comments.data', 1)
            ->where('task.can.submit_for_review', true)
            ->where('task.transaction_state', 'open')
        );
});

test('task index links users into the dedicated task workflow page', function () {
    $operations = makeDepartment('Operations');
    [$manager, $managerStaff] = makeStaffUser($operations, 'show.manager@example.test', asManager: true);
    [$assignee] = makeStaffUser($operations, 'show.assignee@example.test', manager: $managerStaff);

    $task = WorkTask::query()->create([
        'title' => 'Dedicated task page',
        'status' => 'pending_review',
        'priority' => 'high',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $assignee->id,
        'assigned_department_id' => $operations->id,
        'submitted_for_review_at' => now(),
        'submitted_by_user_id' => $assignee->id,
    ]);

    $this->actingAs($manager)
        ->get(route('task-management.tasks.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $task->id)
            ->where('tasks.data.0.transaction_state', 'open')
        );

    $this->actingAs($manager)
        ->get(route('task-management.tasks.show', $task))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Show')
            ->where('task.id', $task->id)
            ->where('task.status', 'pending_review')
            ->where('task.can.approve_completion', true)
            ->has('task.history.data')
            ->has('assignees')
            ->has('departments')
        );
});

test('task management exposes linked marketing operations and filters tasks by marketing linkage', function () {
    Permission::firstOrCreate(['name' => 'marketing.requests.create', 'guard_name' => 'web']);

    $marketing = makeDepartment('Marketing');
    [$manager, $managerStaff] = makeStaffUser($marketing, 'linked.marketing.manager@example.test', asManager: true);
    [$assignee] = makeStaffUser($marketing, 'linked.marketing.assignee@example.test', manager: $managerStaff);
    $manager->givePermissionTo('marketing.requests.create');

    $linkedTask = WorkTask::query()->create([
        'title' => 'Linked campaign task',
        'status' => 'open',
        'priority' => 'high',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $assignee->id,
        'assigned_department_id' => $marketing->id,
    ]);

    $unlinkedTask = WorkTask::query()->create([
        'title' => 'Plain operational task',
        'status' => 'open',
        'priority' => 'medium',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $assignee->id,
        'assigned_department_id' => $marketing->id,
    ]);

    $requestRecord = MarketingRequest::query()->create([
        'title' => 'Campaign collateral governance',
        'objective' => 'Coordinate collateral around the task.',
        'requester_user_id' => $manager->id,
        'owner_department_id' => $marketing->id,
        'priority' => 'high',
        'status' => 'submitted',
        'work_task_id' => $linkedTask->id,
    ]);

    $deliverableOnlyRequest = MarketingRequest::query()->create([
        'title' => 'Deliverable-only marketing support',
        'objective' => 'Link only one deliverable back to the task.',
        'requester_user_id' => $manager->id,
        'owner_department_id' => $marketing->id,
        'priority' => 'medium',
        'status' => 'submitted',
    ]);
    $deliverableOnlyPackage = $deliverableOnlyRequest->workPackages()->create([
        'assigned_unit' => 'graphics',
        'workload_status' => 'submitted',
    ]);

    $linkedDeliverable = MarketingDeliverable::query()->create([
        'request_id' => $deliverableOnlyRequest->id,
        'work_package_id' => $deliverableOnlyPackage->id,
        'title' => 'Task-linked artwork',
        'deliverable_type' => 'poster',
        'assigned_unit' => 'graphics',
        'status' => 'queued',
        'work_task_id' => $linkedTask->id,
    ]);

    $this->actingAs($manager)
        ->get(route('task-management.tasks.show', $linkedTask))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Show')
            ->where('task.id', $linkedTask->id)
            ->where('task.marketing_operations.0.id', $requestRecord->id)
            ->where('task.marketing_operations.0.title', $requestRecord->title)
            ->where('task.marketing_deliverables.0.id', $linkedDeliverable->id)
            ->where('task.marketing_deliverables.0.title', $linkedDeliverable->title)
            ->where('canRegisterMarketingOperation', true)
        );

    $this->actingAs($manager)
        ->get(route('task-management.tasks.index', ['marketing_operations' => 'linked']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $linkedTask->id)
            ->where('tasks.data.0.marketing_operations_count', 2)
            ->where('filters.marketing_operations', 'linked')
        );

    $this->actingAs($manager)
        ->get(route('task-management.tasks.index', ['marketing_operations' => 'unlinked']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $unlinkedTask->id)
            ->where('tasks.data.0.marketing_operations_count', 0)
            ->where('filters.marketing_operations', 'unlinked')
        );
});

test('task management dashboard exposes operational workload and queue summaries', function () {
    $operations = makeDepartment('Operations');
    $technical = makeDepartment('Technical');
    [$manager, $managerStaff] = makeStaffUser($operations, 'dashboard.manager@example.test', asManager: true);
    [$report] = makeStaffUser($operations, 'dashboard.report@example.test', manager: $managerStaff);
    [$technicalUser] = makeStaffUser($technical, 'dashboard.responder@example.test');
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $task = WorkTask::query()->create([
        'title' => 'Late report',
        'status' => 'open',
        'priority' => 'high',
        'due_date' => now()->subDay()->toDateString(),
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $report->id,
        'assigned_department_id' => $operations->id,
    ]);

    $ticket = SupportTicket::query()->create([
        'title' => 'Critical printer outage',
        'description' => 'Printing unavailable.',
        'status' => 'open',
        'priority' => 'urgent',
        'requester_user_id' => $manager->id,
        'requester_department_id' => $operations->id,
        'assigned_department_id' => $technical->id,
    ]);
    $ticket->forceFill([
        'created_at' => now()->subHours(12),
        'updated_at' => now()->subHours(12),
    ])->save();

    $this->actingAs($manager)
        ->get(route('task-management.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Dashboard')
            ->where('dashboard.tasks.summary.overdue', 1)
            ->where('dashboard.tasks.overdue_tasks.0.id', $task->id)
            ->where('dashboard.tickets.summary.overdue', 1)
            ->where('dashboard.tickets.overdue_tickets.0.id', $ticket->id)
        );
});

test('task reminder command dispatches queue job and can run immediate overdue notifications', function () {
    Queue::fake();
    Notification::fake();

    $operations = makeDepartment('Operations');
    $technical = makeDepartment('Technical');
    [$manager] = makeStaffUser($operations, 'reminder.manager@example.test', asManager: true);
    [$technicalUser] = makeStaffUser($technical, 'reminder.tech@example.test');
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $task = WorkTask::query()->create([
        'title' => 'Missed deadline',
        'status' => 'open',
        'priority' => 'high',
        'due_date' => now()->subDay()->toDateString(),
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $operations->id,
        'assigned_to_user_id' => $manager->id,
        'assigned_department_id' => $operations->id,
    ]);

    $ticket = SupportTicket::query()->create([
        'title' => 'Server access issue',
        'description' => 'Urgent restore needed.',
        'status' => 'open',
        'priority' => 'urgent',
        'requester_user_id' => $manager->id,
        'requester_department_id' => $operations->id,
        'assigned_to_user_id' => $technicalUser->id,
        'assigned_department_id' => $technical->id,
    ]);
    $ticket->forceFill([
        'created_at' => now()->subHours(12),
        'updated_at' => now()->subHours(12),
    ])->save();

    $this->artisan('task-management:send-reminders')
        ->expectsOutput('Task management reminder job dispatched to the queue.')
        ->assertSuccessful();

    Queue::assertPushed(SendTaskManagementReminderNotificationsJob::class);

    $this->artisan('task-management:send-reminders', ['--now' => true])
        ->expectsOutputToContain('Task reminders sent: 1')
        ->expectsOutputToContain('Ticket reminders sent: 1')
        ->assertSuccessful();

    Notification::assertSentTo($manager, TaskOverdueReminderNotification::class);
    Notification::assertSentTo($technicalUser, SupportTicketOverdueNotification::class);
});
