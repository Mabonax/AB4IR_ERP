<?php

use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Marketing\Notifications\MarketingJobActivityNotification;
use App\Domains\Marketing\Notifications\MarketingJobAssignedNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'domain.marketing.view',
        'domain.marketing.manage',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

function makeMarketingDepartment(string $name): StaffDepartment
{
    return StaffDepartment::query()->create([
        'name' => $name,
        'description' => $name.' department',
    ]);
}

function makeMarketingUser(StaffDepartment $department, string $email, bool $asManager = false, ?StaffMember $manager = null): array
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
        'last_name' => 'Marketing',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => $asManager,
        'is_ceo' => false,
    ]);

    $user->staffMember()->save($staff);
    $user->givePermissionTo($asManager ? ['domain.marketing.view', 'domain.marketing.manage'] : ['domain.marketing.view']);

    return [$user->refresh(), $staff->refresh()];
}

test('marketing manager can create and route marketing work to marketing staff', function () {
    Notification::fake();

    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'marketing.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'marketing.designer@example.test', manager: $managerStaff);

    $event = Event::query()->create([
        'title' => 'Launch Event',
        'event_type' => 'Expo',
        'start_date' => now()->addMonth()->toDateString(),
        'status' => 'planned',
        'owner_staff_member_id' => $managerStaff->id,
    ]);

    $response = $this->actingAs($manager)
        ->post(route('marketing.jobs.store'), [
            'title' => 'Create launch campaign artwork',
            'brief' => 'Build social and print key visuals for the event launch.',
            'job_type' => 'graphic_design',
            'priority' => 'high',
            'due_date' => now()->addWeek()->toDateString(),
            'event_id' => $event->id,
            'assigned_to_user_id' => $designer->id,
            'assigned_department_id' => $marketing->id,
        ]);

    $job = MarketingJob::query()->firstOrFail();

    $response->assertRedirect(route('marketing.jobs.show', $job));

    $this->assertDatabaseHas('marketing_jobs', [
        'id' => $job->id,
        'title' => 'Create launch campaign artwork',
        'job_type' => 'graphic_design',
        'assigned_to_user_id' => $designer->id,
        'event_id' => $event->id,
        'status' => 'open',
    ]);

    Notification::assertSentTo($designer, MarketingJobAssignedNotification::class);
});

test('non manager cannot create marketing work and cross department assignment is blocked', function () {
    $marketing = makeMarketingDepartment('Marketing');
    $technical = makeMarketingDepartment('Technical');
    [$staffUser] = makeMarketingUser($marketing, 'marketing.staff@example.test');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'marketing.manager.block@example.test', asManager: true);
    [$technicalUser] = makeMarketingUser($technical, 'technical.user@example.test');
    $technicalUser->givePermissionTo(['domain.marketing.view']);

    $this->actingAs($staffUser)
        ->post(route('marketing.jobs.store'), [
            'title' => 'Unauthorized work item',
            'brief' => 'Should be blocked.',
            'job_type' => 'social_media',
            'priority' => 'medium',
            'assigned_to_user_id' => $staffUser->id,
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->post(route('marketing.jobs.store'), [
            'title' => 'Wrong department assignment',
            'brief' => 'Should not be routed outside marketing.',
            'job_type' => 'graphic_design',
            'priority' => 'medium',
            'assigned_to_user_id' => $technicalUser->id,
            'assigned_department_id' => $technical->id,
        ])
        ->assertSessionHasErrors(['assigned_department_id']);
});

test('assigned marketing staff can submit work, manager can request amendments, and final approval closes the transaction', function () {
    Storage::fake('local');
    Notification::fake();

    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'approval.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'approval.designer@example.test', manager: $managerStaff);

    $job = MarketingJob::query()->create([
        'title' => 'Email signature rollout',
        'job_type' => 'email_signature',
        'status' => 'in_progress',
        'priority' => 'high',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $designer->id,
        'assigned_department_id' => $marketing->id,
    ]);

    $this->actingAs($designer)
        ->post(route('marketing.jobs.submit-approval', $job), [
            'delivery_notes' => 'Uploaded the final HTML and image pack for review.',
            'proof_url' => 'https://mail.example.test/threads/marketing-signature',
            'proof_file' => UploadedFile::fake()->create('signature-pack.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect(route('marketing.jobs.show', $job));

    $job->refresh();

    expect($job->status)->toBe('pending_approval')
        ->and($job->submitted_by_user_id)->toBe($designer->id)
        ->and($job->closed_at)->toBeNull();

    Storage::disk('local')->assertExists($job->proof_path);

    $this->actingAs($manager)
        ->post(route('marketing.jobs.request-amendments', $job), [
            'approval_notes' => 'Please tighten the spacing and add the alternate contact line.',
        ])
        ->assertRedirect(route('marketing.jobs.show', $job));

    $this->assertDatabaseHas('marketing_jobs', [
        'id' => $job->id,
        'status' => 'changes_requested',
        'approval_notes' => 'Please tighten the spacing and add the alternate contact line.',
        'closed_by_user_id' => null,
    ]);

    $this->actingAs($designer)
        ->post(route('marketing.jobs.submit-approval', $job), [
            'delivery_notes' => 'Updated spacing and alternate contact line added.',
            'proof_file' => UploadedFile::fake()->create('signature-pack-v2.pdf', 80, 'application/pdf'),
        ])
        ->assertRedirect(route('marketing.jobs.show', $job));

    $this->actingAs($manager)
        ->post(route('marketing.jobs.approve', $job), [
            'approval_notes' => 'Approved for organisation-wide rollout.',
        ])
        ->assertRedirect(route('marketing.jobs.show', $job));

    $this->assertDatabaseHas('marketing_jobs', [
        'id' => $job->id,
        'status' => 'approved',
        'closed_by_user_id' => $manager->id,
        'approval_notes' => 'Approved for organisation-wide rollout.',
    ]);

    $this->assertDatabaseHas('marketing_job_histories', [
        'marketing_job_id' => $job->id,
        'action' => 'submitted_for_approval',
    ]);
    $this->assertDatabaseHas('marketing_job_histories', [
        'marketing_job_id' => $job->id,
        'action' => 'changes_requested',
    ]);
    $this->assertDatabaseHas('marketing_job_histories', [
        'marketing_job_id' => $job->id,
        'action' => 'approved',
    ]);

    Notification::assertSentTo($manager, MarketingJobActivityNotification::class);
});

test('assigned marketing staff cannot approve or reassign their own marketing work', function () {
    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'guard.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'guard.designer@example.test', manager: $managerStaff);
    [$otherDesigner] = makeMarketingUser($marketing, 'guard.designer.two@example.test', manager: $managerStaff);

    $job = MarketingJob::query()->create([
        'title' => 'Letterhead revision',
        'job_type' => 'letter_communication',
        'status' => 'pending_approval',
        'priority' => 'medium',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $designer->id,
        'assigned_department_id' => $marketing->id,
        'submitted_for_approval_at' => now(),
        'submitted_by_user_id' => $designer->id,
    ]);

    $this->actingAs($designer)
        ->post(route('marketing.jobs.approve', $job), [
            'approval_notes' => 'Unauthorized self-approval.',
        ])
        ->assertForbidden();

    $this->actingAs($designer)
        ->post(route('marketing.jobs.reassign', $job), [
            'assigned_to_user_id' => $otherDesigner->id,
            'assigned_department_id' => $marketing->id,
            'reason' => 'Unauthorized reassignment.',
        ])
        ->assertForbidden();
});

test('marketing pages expose the dedicated workflow payload and document downloads', function () {
    Storage::fake('local');

    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'page.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'page.designer@example.test', manager: $managerStaff);

    $job = MarketingJob::query()->create([
        'title' => 'Social calendar Q3',
        'brief' => 'Prepare the quarterly social media calendar.',
        'job_type' => 'content_plan',
        'status' => 'open',
        'priority' => 'medium',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $designer->id,
        'assigned_department_id' => $marketing->id,
    ]);

    $this->actingAs($designer)
        ->post(route('marketing.jobs.documents.store', $job), [
            'title' => 'Calendar draft',
            'document_kind' => 'concept',
            'notes' => 'Editable planning version.',
            'file' => UploadedFile::fake()->create('calendar-draft.xlsx', 40, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])
        ->assertRedirect(route('marketing.jobs.show', $job));

    $document = $job->documents()->firstOrFail();

    $this->actingAs($manager)
        ->get(route('marketing.jobs.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Create')
            ->has('events')
            ->has('assignees')
            ->has('departments')
        );

    $this->actingAs($manager)
        ->get(route('marketing.jobs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Index')
            ->where('summary.total', 1)
            ->where('jobs.data.0.id', $job->id)
        );

    $this->actingAs($manager)
        ->get(route('marketing.jobs.show', $job))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Show')
            ->where('job.id', $job->id)
            ->where('job.can.approve', true)
            ->has('job.documents.data', 1)
            ->has('assignees')
        );

    $this->actingAs($manager)
        ->get(route('marketing.jobs.documents.download', [$job, $document]))
        ->assertOk();
});

test('marketing department staff can view all department marketing jobs while only managers can manage them', function () {
    $marketing = makeMarketingDepartment('Marketing');
    $technical = makeMarketingDepartment('Technical');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'visibility.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'visibility.designer@example.test', manager: $managerStaff);
    [$reviewer] = makeMarketingUser($marketing, 'visibility.reviewer@example.test', manager: $managerStaff);
    [$technicalUser] = makeMarketingUser($technical, 'visibility.technical@example.test');
    $technicalUser->givePermissionTo(['domain.marketing.view']);

    $job = MarketingJob::query()->create([
        'title' => 'Department campaign brief',
        'brief' => 'Visible to the full marketing department.',
        'job_type' => 'content_plan',
        'status' => 'pending_approval',
        'priority' => 'high',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $designer->id,
        'assigned_department_id' => $marketing->id,
        'submitted_for_approval_at' => now(),
        'submitted_by_user_id' => $designer->id,
    ]);

    $this->actingAs($reviewer)
        ->get(route('marketing.jobs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Index')
            ->where('summary.total', 1)
            ->where('jobs.data.0.id', $job->id)
            ->where('can.create', false)
        );

    $this->actingAs($reviewer)
        ->get(route('marketing.jobs.show', $job))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Show')
            ->where('job.id', $job->id)
            ->where('job.can.approve', false)
            ->where('job.can.reassign', false)
            ->where('job.can.comment', false)
            ->where('job.can.upload_document', false)
        );

    $this->actingAs($reviewer)
        ->post(route('marketing.jobs.approve', $job), [
            'approval_notes' => 'Unauthorized approval.',
        ])
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->post(route('marketing.jobs.reassign', $job), [
            'assigned_to_user_id' => $reviewer->id,
            'assigned_department_id' => $marketing->id,
            'reason' => 'Unauthorized reassignment.',
        ])
        ->assertForbidden();

    $this->actingAs($technicalUser)
        ->get(route('marketing.jobs.show', $job))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('marketing.jobs.show', $job))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Show')
            ->where('job.id', $job->id)
            ->where('job.can.approve', true)
            ->where('job.can.reassign', true)
        );
});
