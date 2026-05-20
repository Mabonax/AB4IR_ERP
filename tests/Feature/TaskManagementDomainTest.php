<?php

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\TaskManagement\Jobs\SendTaskManagementReminderNotificationsJob;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Notifications\SupportTicketAssignedNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketOverdueNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketResolvedNotification;
use App\Domains\TaskManagement\Notifications\TaskAssignedNotification;
use App\Domains\TaskManagement\Notifications\TaskOverdueReminderNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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
});

test('requester can log ticket and technical responder can assign and resolve it', function () {
    Notification::fake();

    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'tech.responder@example.test');
    $technicalUser->givePermissionTo(['technical-tickets.respond']);

    $this->actingAs($requester)
        ->post(route('task-management.tickets.store'), [
            'title' => 'Laptop not connecting to Wi-Fi',
            'description' => 'Cannot access internal systems.',
            'priority' => 'high',
            'project_id' => '',
            'program_id' => '',
        ])
        ->assertRedirect(route('task-management.tickets.index'));

    $ticket = SupportTicket::query()->firstOrFail();

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
    Notification::assertSentTo($requester, SupportTicketResolvedNotification::class);
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
        ->assertRedirect(route('task-management.tasks.index'));

    $this->actingAs($reportB)
        ->post(route('task-management.tasks.comment', $task), [
            'message' => 'Received and starting work now.',
        ])
        ->assertRedirect(route('task-management.tasks.index'));

    $this->assertDatabaseHas('work_tasks', [
        'id' => $task->id,
        'assigned_to_user_id' => $reportB->id,
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
});

test('ticket index exposes overdue filters and task index exposes history payloads', function () {
    $marketing = makeDepartment('Marketing');
    $technical = makeDepartment('Technical');
    [$requester] = makeStaffUser($marketing, 'dashboard.requester@example.test');
    [$technicalUser] = makeStaffUser($technical, 'dashboard.tech@example.test');
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
        ->get(route('task-management.tasks.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/Tasks/Index')
            ->has('tasks.data', 1)
            ->has('tasks.data.0.history', 1)
            ->has('tasks.data.0.comments', 1)
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
