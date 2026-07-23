<?php

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Models\DocumentLink;
use App\Domains\Documents\Models\DocumentRepositoryTemplate;
use App\Domains\Documents\Models\DocumentVersion;
use App\Domains\Documents\Services\DocumentTemplateService;
use App\Domains\Events\Models\Event;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Services\ProgramService;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeDocumentUser(array $permissions, bool $isManager = false, string $email = 'documents.user@example.test'): array
{
    $user = grantPermissions(User::factory()->create([
        'email' => $email,
        'name' => strtok($email, '@'),
    ]), $permissions);

    $department = StaffDepartment::query()->create([
        'name' => 'Documents '.substr(md5($email), 0, 6),
        'description' => 'Documents department',
    ]);

    $staff = StaffMember::query()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'first_name' => 'Doc',
        'last_name' => 'User',
        'email' => $email,
        'phone' => '0711111111',
        'employee_number' => strtoupper(substr(md5($email), 0, 8)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'is_manager' => $isManager,
        'is_ceo' => false,
    ]);

    $user->staffMember()->save($staff);

    return [$user->refresh(), $staff->refresh(), $department->refresh()];
}

function makeProgramForDocuments(User $actor): Program
{
    return app(ProgramService::class)->create([
        'title' => 'Drone Divas',
        'description' => 'Program for document library coverage.',
        'slug' => 'drone-divas',
    ], $actor);
}

function makeProjectForDocuments(User $actor, Program $program): Project
{
    return app(ProjectService::class)->createProject([
        'program_id' => $program->id,
        'name' => 'Digital Youth Festival',
        'start_date' => now()->toDateString(),
        'status' => 'planned',
        'description' => 'Project for document library coverage.',
    ], $actor);
}

test('folder creation supports managed nested folders', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true);
    $program = makeProgramForDocuments($user);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.folders.store'), [
            'parent_id' => $reports->id,
            'name' => 'Q1 Reports',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $reports->id,
        'name' => 'Q1 Reports',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);
});

test('nested folders remain attached to the same ownership scope', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'nested.docs@example.test');
    $program = makeProgramForDocuments($user);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.folders.store'), [
        'parent_id' => $reports->id,
        'name' => 'Q2',
    ])->assertRedirect();

    $q2 = DocumentFolder::query()->where('parent_id', $reports->id)->where('name', 'Q2')->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.folders.store'), [
        'parent_id' => $q2->id,
        'name' => 'Final Pack',
    ])->assertRedirect();

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $q2->id,
        'name' => 'Final Pack',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);
});

test('program creation provisions default program owned folders', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'program.docs@example.test');
    $program = makeProgramForDocuments($user);

    $root = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('folder_type', DocumentFolder::TYPE_PROGRAM_ROOT)
        ->first();

    expect($root)->not->toBeNull();

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Concept Documents',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Brochures & Posters',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'SLAs & Agreements',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Reports',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Working Files',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);
});

test('project creation provisions default project owned folders', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage', 'domain.projects.view', 'domain.projects.manage'], isManager: true, email: 'project.docs@example.test');
    $program = makeProgramForDocuments($user);
    $project = makeProjectForDocuments($user, $program);

    $root = DocumentFolder::query()
        ->where('owner_type', Project::class)
        ->where('owner_id', $project->id)
        ->where('folder_type', DocumentFolder::TYPE_PROJECT_ROOT)
        ->first();

    expect($root)->not->toBeNull();

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Project Poster',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Brochures',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Concept Documents',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'SLAs & Agreements',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Reports',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Working Files',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);
});

test('authorized users can upload and download document library files', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'upload.docs@example.test');
    $program = makeProgramForDocuments($user);

    $deliverables = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Working Files')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $deliverables->id,
            'title' => 'Launch Pack',
            'description' => 'Approved deliverable pack.',
            'file' => UploadedFile::fake()->create('launch-pack.pdf', 128, 'application/pdf'),
        ])
        ->assertRedirect();

    $file = DocumentFile::query()->where('folder_id', $deliverables->id)->firstOrFail();

    Storage::disk('document_library')->assertExists($file->file_path);
    expect($file->version)->toBe(1);

    $this->actingAs($user)
        ->get(route('organization.document-library.files.download', $file))
        ->assertOk();
});

test('project viewers can upload working files to visible project folders', function () {
    Storage::fake('document_library');

    [$manager] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage', 'domain.projects.view', 'domain.projects.manage'], isManager: true, email: 'project.manager.docs@example.test');
    [$viewer] = makeDocumentUser(['domain.projects.view'], isManager: false, email: 'project.viewer.docs@example.test');
    $program = makeProgramForDocuments($manager);
    $project = makeProjectForDocuments($manager, $program);

    $reports = DocumentFolder::query()
        ->where('owner_type', Project::class)
        ->where('owner_id', $project->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($viewer)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $reports->id,
            'title' => 'Field Notes',
            'description' => 'Captured during implementation.',
            'file' => UploadedFile::fake()->create('field-notes.pdf', 48, 'application/pdf'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('document_files', [
        'folder_id' => $reports->id,
        'title' => 'Field Notes',
    ]);
});

test('document uploads accept office image and text document types', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'document-types.docs@example.test');
    $program = makeProgramForDocuments($user);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    foreach (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'txt', 'csv'] as $extension) {
        $this->actingAs($user)
            ->post(route('organization.document-library.files.store'), [
                'folder_id' => $reports->id,
                'title' => strtoupper($extension).' Document',
                'file' => $extension === 'png'
                    ? UploadedFile::fake()->image("allowed.{$extension}")
                    : UploadedFile::fake()->create("allowed.{$extension}", 24),
            ])
            ->assertSessionDoesntHaveErrors();
    }

    $this->actingAs($user)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $reports->id,
            'title' => 'Archive',
            'file' => UploadedFile::fake()->create('archive.zip', 24),
        ])
        ->assertSessionHasErrors('file');
});

test('upload validation errors are returned for missing files', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'missing-file.docs@example.test');
    $program = makeProgramForDocuments($user);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $reports->id,
            'title' => 'No File',
        ])
        ->assertSessionHasErrors('file');
});

test('library group folders cannot receive direct uploads', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'library-group.docs@example.test');
    makeProgramForDocuments($user);

    $libraryGroup = DocumentFolder::query()
        ->where('folder_type', DocumentFolder::TYPE_LIBRARY_GROUP)
        ->where('name', 'Programs')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $libraryGroup->id,
            'title' => 'Should Fail',
            'description' => 'No uploads into system group nodes.',
            'file' => UploadedFile::fake()->create('should-fail.pdf', 24, 'application/pdf'),
        ])
        ->assertSessionHasErrors('folder_id');

    $this->assertDatabaseCount('document_files', 0);
});

test('permission enforcement hides program documents from unrelated users', function () {
    Storage::fake('document_library');

    [$manager] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'manager.docs@example.test');
    [$outsider] = makeDocumentUser(['domain.stakeholders.view'], isManager: false, email: 'outsider.docs@example.test');
    $program = makeProgramForDocuments($manager);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($manager)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $reports->id,
            'title' => 'Restricted Report',
            'description' => 'Program-only file.',
            'file' => UploadedFile::fake()->create('restricted.pdf', 64, 'application/pdf'),
        ])
        ->assertRedirect();

    $file = DocumentFile::query()->firstOrFail();

    $this->actingAs($outsider)
        ->get(route('organization.document-library.index'))
        ->assertOk()
        ->assertDontSee('Drone Divas');

    $this->actingAs($outsider)
        ->get(route('organization.document-library.files.download', $file))
        ->assertForbidden();
});

test('approved files can publish to the organization vault by reference', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser([
        'domain.programs.view',
        'domain.programs.manage',
        'domain.organization.view',
        'domain.organization.manage',
    ], isManager: true, email: 'vault.docs@example.test');

    $program = makeProgramForDocuments($user);
    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $reports->id,
            'title' => 'Board Report',
            'description' => 'Final approved board report.',
            'file' => UploadedFile::fake()->create('board-report.pdf', 96, 'application/pdf'),
        ])
        ->assertRedirect();

    $file = DocumentFile::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.files.publish-to-vault', $file), [
            'title' => 'Board Report',
            'document_type' => 'other',
            'description' => 'Published by reference from the document library.',
            'audience_scope' => 'all_staff',
            'is_active' => true,
        ])
        ->assertRedirect();

    $document = OrganizationDocument::query()->firstOrFail();

    expect($document->source_type)->toBe(DocumentFile::class);
    expect((int) $document->source_id)->toBe($file->id);
    expect($document->disk)->toBe('document_library');
    expect($document->path)->toBe($file->file_path);
});

test('document links can connect a single file to multiple records without duplicating storage', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser([
        'domain.programs.view',
        'domain.programs.manage',
        'domain.projects.view',
        'domain.projects.manage',
        'domain.events.view',
        'domain.events.manage',
    ], isManager: true, email: 'link.docs@example.test');

    $program = makeProgramForDocuments($user);
    $project = makeProjectForDocuments($user, $program);
    $event = Event::query()->create([
        'title' => 'Innovation Summit',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'status' => 'planned',
    ]);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.files.store'), [
        'folder_id' => $reports->id,
        'title' => 'Funding Agreement',
        'file' => UploadedFile::fake()->create('funding-agreement.pdf', 24, 'application/pdf'),
    ])->assertRedirect();

    $file = DocumentFile::query()->firstOrFail();
    $storedPath = $file->file_path;

    $this->actingAs($user)->post(route('organization.document-library.files.links.store', $file), [
        'linkable_type' => Project::class,
        'linkable_id' => $project->id,
        'relationship_type' => 'contract',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('organization.document-library.files.links.store', $file), [
        'linkable_type' => Event::class,
        'linkable_id' => $event->id,
        'relationship_type' => 'reference',
    ])->assertRedirect();

    expect(DocumentLink::query()->where('document_id', $file->id)->count())->toBe(2);
    expect($file->fresh()->file_path)->toBe($storedPath);
});

test('document workspace supports version uploads and restoration', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'version.docs@example.test');
    $program = makeProgramForDocuments($user);
    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.files.store'), [
        'folder_id' => $reports->id,
        'title' => 'Quarterly Report',
        'file' => UploadedFile::fake()->create('q1.pdf', 24, 'application/pdf'),
    ])->assertRedirect();

    $file = DocumentFile::query()->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.files.versions.store', $file), [
        'file' => UploadedFile::fake()->create('q1-v2.pdf', 32, 'application/pdf'),
        'notes' => 'Updated numbers.',
    ])->assertRedirect();

    $file->refresh();

    expect($file->version)->toBe(2);
    expect(DocumentVersion::query()->where('document_id', $file->id)->count())->toBe(2);

    $firstVersion = DocumentVersion::query()
        ->where('document_id', $file->id)
        ->where('version_number', 1)
        ->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.files.versions.restore', [$file, $firstVersion]))
        ->assertRedirect();

    expect($file->fresh()->version)->toBe(3);
});

test('document approval and check out workflow updates the active file state', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'workflow.docs@example.test');
    $program = makeProgramForDocuments($user);
    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.files.store'), [
        'folder_id' => $reports->id,
        'title' => 'Board Pack',
        'file' => UploadedFile::fake()->create('board-pack.pdf', 24, 'application/pdf'),
    ])->assertRedirect();

    $file = DocumentFile::query()->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.files.checkout', $file))
        ->assertRedirect();
    expect($file->fresh()->checked_out_by)->toBe($user->id);

    $this->actingAs($user)->post(route('organization.document-library.files.checkin', $file), [
        'notes' => 'Reviewed locally.',
    ])->assertRedirect();
    expect($file->fresh()->checked_out_by)->toBeNull();

    $this->actingAs($user)->post(route('organization.document-library.files.submit-review', $file))
        ->assertRedirect();
    expect($file->fresh()->status)->toBe('under_review');

    $this->actingAs($user)->post(route('organization.document-library.files.approve', $file), [
        'comments' => 'Approved for publication.',
    ])->assertRedirect();
    expect($file->fresh()->status)->toBe('approved');
});

test('repository templates can be applied to an existing workspace and are listed in the document workspace page', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'template.docs@example.test');
    $program = makeProgramForDocuments($user);

    app(DocumentTemplateService::class)->ensureDefaults($user);

    $root = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('folder_type', DocumentFolder::TYPE_PROGRAM_ROOT)
        ->firstOrFail();

    $template = DocumentRepositoryTemplate::query()->where('slug', 'training-program-template')->firstOrFail();

    $this->actingAs($user)
        ->post(route('organization.document-library.folders.apply-template', $root), [
            'template_id' => $template->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Certificates',
    ]);

    $this->actingAs($user)
        ->get(route('organization.document-library.index', ['folder' => $root->id]))
        ->assertOk()
        ->assertSee('Training Program Template');
});

test('document workspace search returns matching files and writes audit activity for downloads', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'search.docs@example.test');
    $program = makeProgramForDocuments($user);
    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    $this->actingAs($user)->post(route('organization.document-library.files.store'), [
        'folder_id' => $reports->id,
        'title' => 'Quarterly Search Target',
        'description' => 'Contains the reporting term.',
        'file' => UploadedFile::fake()->create('search-target.pdf', 24, 'application/pdf'),
    ])->assertRedirect();

    $file = DocumentFile::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('organization.document-library.index', ['folder' => $reports->id, 'search' => 'Search Target']))
        ->assertOk()
        ->assertSee('Quarterly Search Target');

    $this->actingAs($user)
        ->get(route('organization.document-library.files.download', $file))
        ->assertOk();

    $this->assertDatabaseHas('document_activity_logs', [
        'document_id' => $file->id,
        'action' => 'downloaded',
        'user_id' => $user->id,
    ]);
});

test('renaming a program keeps its repository root aligned', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'program.rename.docs@example.test');
    $program = makeProgramForDocuments($user);

    app(ProgramService::class)->update($program->id, [
        'title' => 'Drone Divas Updated',
        'description' => $program->description,
        'slug' => 'drone-divas',
    ], $user);

    $this->assertDatabaseHas('document_folders', [
        'owner_type' => Program::class,
        'owner_id' => $program->id,
        'folder_type' => DocumentFolder::TYPE_PROGRAM_ROOT,
        'name' => 'Drone Divas Updated',
    ]);
});

test('renaming a project keeps its repository root aligned', function () {
    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage', 'domain.projects.view', 'domain.projects.manage'], isManager: true, email: 'project.rename.docs@example.test');
    $program = makeProgramForDocuments($user);
    $project = makeProjectForDocuments($user, $program);

    app(ProjectService::class)->updateProject($project->id, [
        'program_id' => $program->id,
        'name' => 'Digital Youth Festival Updated',
        'start_date' => $project->start_date?->format('Y-m-d'),
        'status' => $project->status,
        'description' => $project->description,
    ], $user);

    $this->assertDatabaseHas('document_folders', [
        'owner_type' => Project::class,
        'owner_id' => $project->id,
        'folder_type' => DocumentFolder::TYPE_PROJECT_ROOT,
        'name' => 'Digital Youth Festival Updated',
    ]);
});
