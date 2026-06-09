<?php

use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
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
        'name' => 'Reports',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Marketing',
        'owner_type' => Program::class,
        'owner_id' => $program->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Deliverables',
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
        'name' => 'Sponsors',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Attendance',
        'owner_type' => Project::class,
        'owner_id' => $project->id,
    ]);

    $this->assertDatabaseHas('document_folders', [
        'parent_id' => $root->id,
        'name' => 'Reports',
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
        ->where('name', 'Deliverables')
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

test('document uploads are limited to office document types', function () {
    Storage::fake('document_library');

    [$user] = makeDocumentUser(['domain.programs.view', 'domain.programs.manage'], isManager: true, email: 'document-types.docs@example.test');
    $program = makeProgramForDocuments($user);

    $reports = DocumentFolder::query()
        ->where('owner_type', Program::class)
        ->where('owner_id', $program->id)
        ->where('name', 'Reports')
        ->firstOrFail();

    foreach (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'] as $extension) {
        $this->actingAs($user)
            ->post(route('organization.document-library.files.store'), [
                'folder_id' => $reports->id,
                'title' => strtoupper($extension).' Document',
                'file' => UploadedFile::fake()->create("allowed.{$extension}", 24),
            ])
            ->assertSessionDoesntHaveErrors();
    }

    $this->actingAs($user)
        ->post(route('organization.document-library.files.store'), [
            'folder_id' => $reports->id,
            'title' => 'Image',
            'file' => UploadedFile::fake()->image('image.png'),
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
