<?php

use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Domains\Programs\Models\Program;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\TaskManagement\Models\WorkTask;
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
        'domain.task-management.view',
        'domain.organization.view',
        'domain.organization.manage',
        'marketing.requests.create',
        'marketing.deliverables.assign',
        'marketing.deliverables.approve',
        'marketing.publications.manage',
        'marketing.metrics.import',
        'marketing.assets.archive',
        'marketing.dashboard.performance.view',
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
    $user->givePermissionTo($asManager
        ? [
            'domain.marketing.view',
            'domain.marketing.manage',
            'marketing.requests.create',
            'marketing.deliverables.assign',
            'marketing.deliverables.approve',
            'marketing.publications.manage',
            'marketing.metrics.import',
            'marketing.assets.archive',
            'marketing.dashboard.performance.view',
        ]
        : ['domain.marketing.view']);

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

test('approved marketing jobs can publish replacement-based signature files into the organization vault', function () {
    Storage::fake('local');
    Storage::fake('public');

    $marketing = makeMarketingDepartment('Marketing');
    [$manager] = makeMarketingUser($marketing, 'vault.manager@example.test', asManager: true);
    $manager->givePermissionTo(['domain.organization.manage', 'domain.organization.view']);

    $proof = UploadedFile::fake()->image('signature-v1.png');
    $proofPath = $proof->store('marketing-job-proof/1', 'local');

    $job = MarketingJob::query()->create([
        'title' => 'Company email signature',
        'job_type' => 'email_signature',
        'status' => 'approved',
        'priority' => 'high',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_department_id' => $marketing->id,
        'proof_disk' => 'local',
        'proof_path' => $proofPath,
        'proof_file_name' => 'signature-v1.png',
        'proof_mime_type' => 'image/png',
        'proof_file_size' => $proof->getSize(),
        'approved_at' => now(),
        'closed_at' => now(),
        'closed_by_user_id' => $manager->id,
    ]);

    $this->actingAs($manager)->post(route('marketing.jobs.publish-to-vault', $job), [
        'title' => 'Company email signature',
        'document_type' => 'email_signature',
        'audience_scope' => 'all_staff',
        'slot_key' => 'company_default_signature',
        'replace_existing' => true,
        'source_kind' => 'proof',
    ])->assertRedirect(route('marketing.jobs.show', $job));

    $document = OrganizationDocument::query()->firstOrFail();
    $firstPath = $document->path;

    Storage::disk('public')->assertExists($firstPath);

    $proofV2 = UploadedFile::fake()->image('signature-v2.png');
    $proofPathV2 = $proofV2->store('marketing-job-proof/1', 'local');
    $job->forceFill([
        'proof_path' => $proofPathV2,
        'proof_file_name' => 'signature-v2.png',
        'proof_file_size' => $proofV2->getSize(),
    ])->save();

    $this->actingAs($manager)->post(route('marketing.jobs.publish-to-vault', $job), [
        'title' => 'Company email signature',
        'document_type' => 'email_signature',
        'audience_scope' => 'all_staff',
        'slot_key' => 'company_default_signature',
        'replace_existing' => true,
        'source_kind' => 'proof',
    ])->assertRedirect(route('marketing.jobs.show', $job));

    expect(OrganizationDocument::query()->count())->toBe(1);
    Storage::disk('public')->assertMissing($firstPath);
});

test('approved marketing deliverable assets can be published into the organization vault', function () {
    Storage::fake('public');
    Storage::fake('local');

    $marketing = makeMarketingDepartment('Marketing');
    [$manager] = makeMarketingUser($marketing, 'vault.asset.manager@example.test', asManager: true);
    $manager->givePermissionTo(['domain.organization.manage', 'domain.organization.view']);

    $requestRecord = \App\Domains\Marketing\Models\MarketingRequest::query()->create([
        'title' => 'Organization concept pack',
        'requester_user_id' => $manager->id,
        'owner_department_id' => $marketing->id,
        'priority' => 'high',
        'status' => 'completed',
    ]);

    $workPackage = $requestRecord->workPackages()->create([
        'assigned_unit' => 'graphics',
        'workload_status' => 'completed',
    ]);

    $deliverable = $requestRecord->deliverables()->create([
        'work_package_id' => $workPackage->id,
        'title' => 'Concept document master',
        'deliverable_type' => 'concept_document',
        'assigned_unit' => 'graphics',
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $assetFile = UploadedFile::fake()->create('concept-pack.pdf', 80, 'application/pdf');
    $assetPath = $assetFile->store('marketing-deliverables/1', 'local');
    $version = $deliverable->versions()->create([
        'version_number' => 1,
        'uploaded_by_user_id' => $manager->id,
        'asset_disk' => 'local',
        'asset_path' => $assetPath,
        'asset_file_name' => 'concept-pack.pdf',
        'asset_mime_type' => 'application/pdf',
        'asset_file_size' => $assetFile->getSize(),
        'approval_status' => 'approved',
        'approved_by_user_id' => $manager->id,
        'approved_at' => now(),
    ]);

    $asset = $deliverable->assets()->create([
        'deliverable_version_id' => $version->id,
        'asset_type' => 'concept_document',
        'asset_disk' => 'local',
        'asset_path' => $assetPath,
        'asset_file_name' => 'concept-pack.pdf',
        'asset_mime_type' => 'application/pdf',
        'asset_file_size' => $assetFile->getSize(),
        'reusable' => true,
    ]);

    $this->actingAs($manager)->post(route('marketing.assets.publish-to-vault', $asset), [
        'title' => 'Concept document master',
        'document_type' => 'concept_document',
        'audience_scope' => 'all_staff',
        'slot_key' => 'concept_document_master',
        'replace_existing' => true,
    ])->assertRedirect(route('marketing.requests.show', $requestRecord));

    $this->assertDatabaseHas('organization_documents', [
        'document_type' => 'concept_document',
        'source_type' => \App\Domains\Marketing\Models\MarketingAsset::class,
        'source_id' => $asset->id,
    ]);
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

test('marketing manager can create request deliverables approve a version and publish an approved asset', function () {
    Storage::fake('local');

    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'ops.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'ops.designer@example.test', manager: $managerStaff);

    $this->actingAs($manager)
        ->post(route('marketing.requests.store'), [
            'title' => 'Project launch communications pack',
            'objective' => 'Create the initial launch package.',
            'description' => 'This request should produce multiple outputs.',
            'campaign_goal' => 'Launch awareness',
            'priority' => 'high',
            'owner_department_id' => $marketing->id,
            'work_package' => [
                'assigned_unit' => 'graphics',
                'operational_owner_user_id' => $manager->id,
            ],
            'deliverables' => [
                [
                    'title' => 'Main poster',
                    'deliverable_type' => 'poster',
                    'assigned_to_user_id' => $designer->id,
                    'assigned_unit' => 'graphics',
                ],
                [
                    'title' => 'Email signature pack',
                    'deliverable_type' => 'email_signature',
                    'assigned_to_user_id' => $designer->id,
                    'assigned_unit' => 'graphics',
                ],
            ],
        ])
        ->assertRedirect();

    $requestRecord = \App\Domains\Marketing\Models\MarketingRequest::query()->firstOrFail();
    $deliverable = $requestRecord->deliverables()->where('title', 'Main poster')->firstOrFail();

    $this->actingAs($designer)
        ->post(route('marketing.deliverables.versions.store', $deliverable), [
            'change_notes' => 'First full poster revision uploaded.',
            'asset_file' => UploadedFile::fake()->create('poster-v1.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $deliverable->refresh();
    expect($deliverable->status)->toBe('internal_review');

    $this->actingAs($manager)
        ->post(route('marketing.deliverables.approve', $deliverable), [
            'review_notes' => 'Approved for initial rollout.',
            'reusable' => true,
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $asset = $deliverable->assets()->firstOrFail();

    $this->actingAs($manager)
        ->post(route('marketing.assets.publish', $asset), [
            'publication_channel' => 'Facebook',
            'published_at' => now()->toDateTimeString(),
            'external_reference' => 'https://facebook.example.test/post/launch',
            'publication_notes' => 'Launch poster published.',
            'metrics' => [
                'metric_date' => now()->toDateString(),
                'reach' => 1000,
                'impressions' => 1500,
                'engagements' => 140,
            ],
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $this->assertDatabaseHas('marketing_requests', [
        'id' => $requestRecord->id,
        'title' => 'Project launch communications pack',
    ]);
    $this->assertDatabaseHas('marketing_deliverables', [
        'id' => $deliverable->id,
        'status' => 'published',
    ]);
    $this->assertDatabaseHas('marketing_assets', [
        'id' => $asset->id,
        'reusable' => 1,
    ]);
    $this->assertDatabaseHas('marketing_publication_records', [
        'marketing_asset_id' => $asset->id,
        'publication_channel' => 'Facebook',
    ]);
    $this->assertDatabaseHas('marketing_metric_snapshots', [
        'reach' => 1000,
        'impressions' => 1500,
    ]);

    $this->actingAs($manager)
        ->post(route('marketing.assets.archive', $asset))
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $this->assertDatabaseHas('marketing_assets', [
        'id' => $asset->id,
    ]);
    expect($asset->fresh()->archived_at)->not->toBeNull();
});

test('marketing operations screens render new request and dashboard surfaces', function () {
    $marketing = makeMarketingDepartment('Marketing');
    [$manager] = makeMarketingUser($marketing, 'ops.screen.manager@example.test', asManager: true);
    $program = Program::query()->create([
        'title' => 'Enterprise Growth Programme',
        'description' => 'Programme context for marketing requests.',
        'slug' => 'enterprise-growth-programme',
    ]);

    $this->actingAs($manager)
        ->get(route('marketing.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Dashboard')
            ->has('dashboard.operations')
            ->has('dashboard.performance')
        );

    $this->actingAs($manager)
        ->get(route('marketing.requests.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Requests/Index')
            ->has('requests.data')
        );

    $this->actingAs($manager)
        ->get(route('marketing.requests.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Requests/Create')
            ->where('programs.0.title', $program->title)
            ->has('deliverableTypes')
            ->has('units')
        );
});

test('marketing operation creation can be preselected from and linked to a work task', function () {
    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'ops.task.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'ops.task.designer@example.test', manager: $managerStaff);
    $manager->givePermissionTo('domain.task-management.view');

    $task = WorkTask::query()->create([
        'title' => 'Launch campaign task',
        'description' => 'Task Management owns assignment and closure.',
        'status' => 'open',
        'priority' => 'high',
        'context_type' => 'general',
        'creator_user_id' => $manager->id,
        'creator_department_id' => $marketing->id,
        'assigned_to_user_id' => $designer->id,
        'assigned_department_id' => $marketing->id,
    ]);

    $this->actingAs($manager)
        ->get(route('marketing.requests.create', ['work_task_id' => $task->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Requests/Create')
            ->where('selectedWorkTaskId', $task->id)
            ->where('workTasks.0.id', $task->id)
            ->where('workTasks.0.title', $task->title)
        );

    $this->actingAs($manager)
        ->post(route('marketing.requests.store'), [
            'title' => 'Linked campaign operation',
            'objective' => 'Govern campaign deliverables for the assigned task.',
            'description' => 'Marketing Operations tracks the collateral and publishing layer.',
            'campaign_goal' => 'Launch awareness',
            'priority' => 'high',
            'owner_department_id' => $marketing->id,
            'work_task_id' => $task->id,
            'work_package' => [
                'assigned_unit' => 'digital',
                'operational_owner_user_id' => $manager->id,
            ],
            'deliverables' => [
                [
                    'title' => 'Launch poster',
                    'deliverable_type' => 'poster',
                    'assigned_to_user_id' => $designer->id,
                    'assigned_unit' => 'graphics',
                ],
            ],
        ])
        ->assertRedirect();

    $requestRecord = \App\Domains\Marketing\Models\MarketingRequest::query()->firstOrFail();
    $deliverable = $requestRecord->deliverables()->firstOrFail();

    expect($requestRecord->work_task_id)->toBe($task->id)
        ->and($deliverable->work_task_id)->toBeNull();

    $this->actingAs($manager)
        ->get(route('marketing.requests.show', $requestRecord))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Requests/Show')
            ->where('requestRecord.work_task_id', $task->id)
            ->where('requestRecord.work_task_title', $task->title)
            ->where('requestRecord.deliverables.data.0.work_task_id', $task->id)
        );
});

test('marketing request workspace supports replanning comments documents and metric imports', function () {
    Storage::fake('local');

    $marketing = makeMarketingDepartment('Marketing');
    [$manager, $managerStaff] = makeMarketingUser($marketing, 'ops.workspace.manager@example.test', asManager: true);
    [$designer] = makeMarketingUser($marketing, 'ops.workspace.designer@example.test', manager: $managerStaff);

    $this->actingAs($manager)
        ->post(route('marketing.requests.store'), [
            'title' => 'Campaign launch pack',
            'objective' => 'Initial campaign assets',
            'description' => 'Coordinate the launch package.',
            'campaign_goal' => 'Awareness',
            'priority' => 'medium',
            'owner_department_id' => $marketing->id,
            'work_package' => [
                'assigned_unit' => 'graphics',
                'operational_owner_user_id' => $manager->id,
                'planned_start_date' => now()->toDateString(),
            ],
            'deliverables' => [
                [
                    'title' => 'Launch poster',
                    'deliverable_type' => 'poster',
                    'assigned_to_user_id' => $designer->id,
                    'assigned_unit' => 'graphics',
                ],
            ],
        ])
        ->assertRedirect();

    /** @var \App\Domains\Marketing\Models\MarketingRequest $requestRecord */
    $requestRecord = \App\Domains\Marketing\Models\MarketingRequest::query()->firstOrFail();
    $deliverable = $requestRecord->deliverables()->firstOrFail();

    $this->actingAs($manager)
        ->put(route('marketing.requests.update', $requestRecord), [
            'title' => 'Campaign launch pack updated',
            'objective' => 'Updated campaign assets',
            'description' => 'Replanned launch package.',
            'target_audience' => 'Community partners',
            'campaign_goal' => 'Engagement',
            'approver_user_id' => $manager->id,
            'owner_department_id' => $marketing->id,
            'priority' => 'high',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'planned',
            'work_package' => [
                'assigned_unit' => 'digital',
                'operational_owner_user_id' => $manager->id,
                'planned_start_date' => now()->addDay()->toDateString(),
                'planned_end_date' => now()->addDays(9)->toDateString(),
            ],
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $requestRecord->refresh();
    $deliverable->refresh();

    expect($requestRecord->title)->toBe('Campaign launch pack updated')
        ->and($requestRecord->status)->toBe('planned')
        ->and($requestRecord->priority)->toBe('high')
        ->and($requestRecord->target_audience)->toBe('Community partners')
        ->and($requestRecord->workPackages()->firstOrFail()->assigned_unit)->toBe('digital')
        ->and($deliverable->due_date?->toDateString())->toBe(now()->addDays(10)->toDateString());

    $this->actingAs($designer)
        ->post(route('marketing.requests.comment', $requestRecord), [
            'message' => 'Poster draft is in progress and awaiting image selection.',
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $this->assertDatabaseHas('marketing_request_comments', [
        'marketing_request_id' => $requestRecord->id,
        'user_id' => $designer->id,
        'message' => 'Poster draft is in progress and awaiting image selection.',
    ]);

    $this->actingAs($designer)
        ->post(route('marketing.requests.documents.store', $requestRecord), [
            'title' => 'Creative brief',
            'document_kind' => 'brief',
            'notes' => 'Updated brief for the launch pack.',
            'file' => UploadedFile::fake()->create('creative-brief.pdf', 60, 'application/pdf'),
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $document = $requestRecord->documents()->firstOrFail();
    Storage::disk('local')->assertExists($document->path);

    $this->actingAs($manager)
        ->get(route('marketing.requests.documents.download', [$requestRecord, $document]))
        ->assertOk();

    $this->actingAs($designer)
        ->post(route('marketing.deliverables.versions.store', $deliverable), [
            'change_notes' => 'Poster draft ready for review.',
            'asset_file' => UploadedFile::fake()->create('poster-v1.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $this->actingAs($manager)
        ->post(route('marketing.deliverables.approve', $deliverable), [
            'review_notes' => 'Approved for social rollout.',
            'reusable' => true,
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $asset = $deliverable->fresh()->assets()->firstOrFail();

    $this->actingAs($manager)
        ->post(route('marketing.assets.publish', $asset), [
            'publication_channel' => 'Facebook',
            'published_at' => now()->toDateTimeString(),
            'external_reference' => 'https://facebook.example.test/post/campaign-launch',
            'metrics' => [
                'metric_date' => now()->toDateString(),
                'reach' => 150,
            ],
        ])
        ->assertRedirect(route('marketing.requests.show', $requestRecord));

    $publication = $asset->fresh()->publications()->firstOrFail();

    $csv = implode("\n", [
        'publication_record_id,metric_date,impressions,reach,engagements,clicks,sessions,conversions,followers',
        sprintf('%d,%s,2000,1200,220,40,25,5,12', $publication->id, now()->toDateString()),
    ]);

    $this->actingAs($manager)
        ->post(route('marketing.publications.import-metrics'), [
            'file' => UploadedFile::fake()->createWithContent('marketing-metrics.csv', $csv),
        ])
        ->assertRedirect(route('marketing.publications.index'));

    $snapshot = $publication->fresh()->metricSnapshots()->firstOrFail();

    expect($snapshot->metric_date?->toDateString())->toBe(now()->toDateString())
        ->and($snapshot->impressions)->toBe(2000)
        ->and($snapshot->reach)->toBe(1200)
        ->and($snapshot->engagements)->toBe(220)
        ->and($snapshot->clicks)->toBe(40)
        ->and($snapshot->sessions)->toBe(25)
        ->and($snapshot->conversions)->toBe(5)
        ->and($snapshot->followers)->toBe(12);

    $this->actingAs($designer)
        ->get(route('marketing.requests.show', $requestRecord))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Marketing/Requests/Show')
            ->where('requestRecord.can.update', false)
            ->where('requestRecord.can.comment', true)
            ->where('requestRecord.can.upload_document', true)
            ->has('requestRecord.comments.data', 1)
            ->has('requestRecord.documents.data', 1)
        );
});
