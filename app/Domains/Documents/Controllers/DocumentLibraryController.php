<?php

namespace App\Domains\Documents\Controllers;

use App\Domains\Assets\Models\Asset;
use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Documents\Models\DocumentFile;
use App\Domains\Documents\Models\DocumentFolder;
use App\Domains\Documents\Models\DocumentLink;
use App\Domains\Documents\Models\DocumentRepositoryTemplate;
use App\Domains\Documents\Models\DocumentVersion;
use App\Domains\Documents\Requests\ApplyDocumentTemplateRequest;
use App\Domains\Documents\Requests\CheckInDocumentRequest;
use App\Domains\Documents\Requests\DocumentApprovalActionRequest;
use App\Domains\Documents\Requests\LinkDocumentRequest;
use App\Domains\Documents\Requests\MoveDocumentFileRequest;
use App\Domains\Documents\Requests\MoveDocumentFolderRequest;
use App\Domains\Documents\Requests\PublishDocumentFileToVaultRequest;
use App\Domains\Documents\Requests\RenameDocumentFileRequest;
use App\Domains\Documents\Requests\RenameDocumentFolderRequest;
use App\Domains\Documents\Requests\StoreDocumentRootFolderRequest;
use App\Domains\Documents\Requests\StoreDocumentFolderRequest;
use App\Domains\Documents\Requests\StoreDocumentTemplateRequest;
use App\Domains\Documents\Requests\UploadDocumentFileRequest;
use App\Domains\Documents\Requests\UploadDocumentFileToVaultRequest;
use App\Domains\Documents\Requests\UploadDocumentVersionRequest;
use App\Domains\Documents\Resources\DocumentFileResource;
use App\Domains\Documents\Resources\DocumentFolderResource;
use App\Domains\Documents\Services\DocumentAccessService;
use App\Domains\Documents\Services\DocumentApprovalService;
use App\Domains\Documents\Services\DocumentFileService;
use App\Domains\Documents\Services\DocumentFolderService;
use App\Domains\Documents\Services\DocumentLinkService;
use App\Domains\Documents\Services\DocumentPreviewService;
use App\Domains\Documents\Services\DocumentSearchService;
use App\Domains\Documents\Services\DocumentTemplateService;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Models\EventSeries;
use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Organization\Enums\OrganizationDocumentSlot;
use App\Domains\Organization\Enums\OrganizationDocumentType;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Domains\Organization\Models\OrganizationProfile;
use App\Domains\Organization\Services\OrganizationDocumentVaultService;
use App\Domains\Organization\Services\OrganizationProfileService;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DocumentLibraryController extends Controller
{
    public function __construct(
        protected DocumentFolderService $folderService,
        protected DocumentFileService $fileService,
        protected OrganizationDocumentVaultService $vaultService,
        protected OrganizationProfileService $profileService,
        protected DocumentLinkService $linkService,
        protected DocumentApprovalService $approvalService,
        protected DocumentTemplateService $templateService,
        protected DocumentPreviewService $previewService,
        protected DocumentSearchService $searchService,
        protected DocumentAccessService $accessService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', DocumentFolder::class);
        $user = request()->user();
        $this->templateService->ensureDefaults($user);

        $workspace = $this->folderService->workspace(
            $user,
            request()->integer('folder') ?: null
        );

        return Inertia::render('Organization/DocumentLibrary/Index', [
            'tree' => $workspace['tree'],
            'breadcrumbs' => $workspace['breadcrumbs'],
            'selectedFolder' => $workspace['selected_folder'] ? new DocumentFolderResource($workspace['selected_folder']) : null,
            'folders' => DocumentFolderResource::collection($workspace['content_folders']),
            'files' => DocumentFileResource::collection($workspace['content_files']),
            'moveTargets' => DocumentFolderResource::collection($workspace['move_targets']),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'vaultDocumentTypes' => OrganizationDocumentType::options(),
            'vaultSlotOptions' => OrganizationDocumentSlot::options(),
            'canPublishToVault' => $user?->can('create', OrganizationDocument::class) ?? false,
            'ownerOptions' => $this->ownerOptions($user),
            'linkOptions' => $this->linkService->options($user),
            'relationshipTypes' => $this->linkService->relationshipTypes(),
            'templates' => DocumentRepositoryTemplate::query()->with('allItems')->orderBy('name')->get()->map(fn (DocumentRepositoryTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'owner_type' => $template->owner_type,
                'is_system' => $template->is_system,
                'items' => $template->allItems->pluck('name')->all(),
            ])->values()->all(),
            'search' => [
                'term' => (string) request('search', ''),
                'status' => (string) request('status', ''),
                'results' => DocumentFileResource::collection(
                    $this->searchService->search(
                        $user,
                        request('search'),
                        request('status')
                    )
                ),
            ],
            'statusOptions' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'under_review', 'label' => 'Under Review'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'archived', 'label' => 'Archived'],
            ],
        ]);
    }

    public function previewFile(DocumentFile $file): HttpResponse
    {
        $this->authorize('view', $file);

        return $this->previewService->previewResponse($file);
    }

    public function storeRootFolder(StoreDocumentRootFolderRequest $request)
    {
        $folder = $this->folderService->createOwnedRootFolder($request->validated(), $request->user());

        return redirect()->route('organization.document-library.index', ['folder' => $folder->id])
            ->with('success', 'Workspace created.');
    }

    public function storeFolder(StoreDocumentFolderRequest $request)
    {
        $parent = DocumentFolder::query()->findOrFail((int) $request->validated('parent_id'));
        $this->authorize('update', $parent);

        $folder = $this->folderService->createFolder($parent, $request->validated(), $request->user());

        return redirect()->route('organization.document-library.index', ['folder' => $folder->parent_id])
            ->with('success', 'Folder created.');
    }

    public function renameFolder(RenameDocumentFolderRequest $request, DocumentFolder $folder)
    {
        $this->authorize('update', $folder);

        $this->folderService->renameFolder($folder, (string) $request->validated('name'), $request->user());

        return redirect()->back()->with('success', 'Folder renamed.');
    }

    public function moveFolder(MoveDocumentFolderRequest $request, DocumentFolder $folder)
    {
        $this->authorize('update', $folder);

        $targetParent = DocumentFolder::query()->findOrFail((int) $request->validated('parent_id'));
        $this->authorize('update', $targetParent);

        $this->folderService->moveFolder($folder, $targetParent, $request->user());

        return redirect()->back()->with('success', 'Folder moved.');
    }

    public function destroyFolder(DocumentFolder $folder)
    {
        $this->authorize('delete', $folder);

        $parentId = $folder->parent_id;
        $this->folderService->deleteFolder($folder, request()->user());

        return redirect()->route('organization.document-library.index', ['folder' => $parentId])
            ->with('success', 'Folder deleted.');
    }

    public function applyTemplate(ApplyDocumentTemplateRequest $request, DocumentFolder $folder)
    {
        $this->authorize('update', $folder);
        $template = DocumentRepositoryTemplate::query()->findOrFail((int) $request->validated('template_id'));

        $this->templateService->apply($template, $folder, $request->user());

        return redirect()->back()->with('success', 'Template applied.');
    }

    public function storeTemplate(StoreDocumentTemplateRequest $request)
    {
        $template = $this->templateService->store($request->validated(), $request->user());

        return redirect()->route('organization.document-library.index')
            ->with('success', 'Template created: '.$template->name);
    }

    public function storeFile(UploadDocumentFileRequest $request)
    {
        $folder = DocumentFolder::query()->findOrFail((int) $request->validated('folder_id'));
        $this->authorize('view', $folder);

        $this->fileService->uploadFile($folder, $request->validated(), $request->user());

        return redirect()->route('organization.document-library.index', ['folder' => $folder->id])
            ->with('success', 'File uploaded.');
    }

    public function storeFileAndPublishToVault(UploadDocumentFileToVaultRequest $request)
    {
        $folder = DocumentFolder::query()->findOrFail((int) $request->validated('folder_id'));
        $this->authorize('view', $folder);
        $this->authorize('create', OrganizationDocument::class);

        $file = $this->fileService->uploadFile($folder, $request->validated(), $request->user());
        $this->vaultService->publishFromDocumentFile($file, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'File uploaded to this folder and published to the organization vault.');
    }

    public function uploadVersion(UploadDocumentVersionRequest $request, DocumentFile $file)
    {
        $this->authorize('version', $file);

        $this->fileService->uploadNewVersion($file, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'New version uploaded.');
    }

    public function restoreVersion(DocumentFile $file, DocumentVersion $version)
    {
        $this->authorize('version', $file);

        $this->fileService->restoreVersion($file, $version, request()->user());

        return redirect()->back()->with('success', 'Version restored as the latest version.');
    }

    public function renameFile(RenameDocumentFileRequest $request, DocumentFile $file)
    {
        $this->authorize('update', $file);

        $this->fileService->renameFile($file, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'File updated.');
    }

    public function moveFile(MoveDocumentFileRequest $request, DocumentFile $file)
    {
        $this->authorize('update', $file);

        $targetFolder = DocumentFolder::query()->findOrFail((int) $request->validated('folder_id'));
        $this->authorize('update', $targetFolder);

        $this->fileService->moveFile($file, $targetFolder, $request->user());

        return redirect()->back()->with('success', 'File moved.');
    }

    public function destroyFile(DocumentFile $file)
    {
        $this->authorize('delete', $file);

        $folderId = $file->folder_id;
        $this->fileService->deleteFile($file, request()->user());

        return redirect()->route('organization.document-library.index', ['folder' => $folderId])
            ->with('success', 'File deleted.');
    }

    public function downloadFile(DocumentFile $file): HttpResponse
    {
        $this->authorize('view', $file);

        return $this->fileService->downloadFile($file, request()->user());
    }

    public function checkOutFile(DocumentFile $file)
    {
        $this->authorize('checkout', $file);

        $this->fileService->checkOut($file, request()->user());

        return redirect()->back()->with('success', 'Document checked out.');
    }

    public function checkInFile(CheckInDocumentRequest $request, DocumentFile $file)
    {
        $this->authorize('checkout', $file);

        $this->fileService->checkIn($file, $request->user(), $request->validated('notes'));

        return redirect()->back()->with('success', 'Document checked in.');
    }

    public function forceReleaseFile(DocumentFile $file)
    {
        $this->authorize('checkout', $file);

        $this->fileService->forceRelease($file, request()->user());

        return redirect()->back()->with('success', 'Document check-out released.');
    }

    public function submitForReview(DocumentApprovalActionRequest $request, DocumentFile $file)
    {
        $this->authorize('update', $file);

        $this->approvalService->submitForReview($file, $request->user(), $request->validated('comments'));

        return redirect()->back()->with('success', 'Document submitted for review.');
    }

    public function approveFile(DocumentApprovalActionRequest $request, DocumentFile $file)
    {
        $this->authorize('approve', $file);

        $this->approvalService->approve($file, $request->user(), $request->validated('comments'));

        return redirect()->back()->with('success', 'Document approved.');
    }

    public function rejectFile(DocumentApprovalActionRequest $request, DocumentFile $file)
    {
        $this->authorize('approve', $file);

        $this->approvalService->reject($file, $request->user(), $request->validated('comments'));

        return redirect()->back()->with('success', 'Document returned to draft.');
    }

    public function archiveFile(DocumentApprovalActionRequest $request, DocumentFile $file)
    {
        $this->authorize('approve', $file);

        $this->approvalService->archive($file, $request->user(), $request->validated('comments'));

        return redirect()->back()->with('success', 'Document archived.');
    }

    public function linkFile(LinkDocumentRequest $request, DocumentFile $file)
    {
        $this->authorize('update', $file);

        $this->linkService->link(
            $file,
            (string) $request->validated('linkable_type'),
            (int) $request->validated('linkable_id'),
            (string) $request->validated('relationship_type'),
            $request->user()
        );

        return redirect()->back()->with('success', 'Document linked.');
    }

    public function unlinkFile(DocumentFile $file, DocumentLink $link)
    {
        $this->authorize('update', $file);

        $this->linkService->unlink($file, $link, request()->user());

        return redirect()->back()->with('success', 'Document link removed.');
    }

    public function publishToVault(PublishDocumentFileToVaultRequest $request, DocumentFile $file)
    {
        $this->authorize('view', $file);
        $this->authorize('create', OrganizationDocument::class);

        $this->vaultService->publishFromDocumentFile($file, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'File published to the organization vault.');
    }

    protected function ownerOptions(User $user): array
    {
        $profile = $this->profileService->getProfile();

        return collect([
            [
                'label' => 'Organization',
                'owner_type' => OrganizationProfile::class,
                'items' => [[
                    'id' => $profile->id,
                    'name' => $profile->name ?: 'Organization',
                ]],
            ],
            [
                'label' => 'Programs',
                'owner_type' => Program::class,
                'items' => Program::query()->orderBy('title')->get(['id', 'title'])->map(fn (Program $program) => [
                    'id' => $program->id,
                    'name' => $program->title,
                ])->values()->all(),
            ],
            [
                'label' => 'Projects',
                'owner_type' => Project::class,
                'items' => Project::query()->orderBy('name')->get(['id', 'name'])->map(fn (Project $project) => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])->values()->all(),
            ],
            [
                'label' => 'Project Locations',
                'owner_type' => ProjectLocation::class,
                'items' => ProjectLocation::query()->with('project:id,name')->orderBy('id')->get(['id', 'project_id', 'training_venue_address'])->map(
                    fn (ProjectLocation $location) => [
                        'id' => $location->id,
                        'name' => trim(($location->project?->name ? $location->project->name.' - ' : '').($location->training_venue_address ?: 'Location #'.$location->id)),
                    ]
                )->values()->all(),
            ],
            [
                'label' => 'Event Lines',
                'owner_type' => EventSeries::class,
                'items' => EventSeries::query()->orderBy('name')->get(['id', 'name'])->map(fn (EventSeries $series) => [
                    'id' => $series->id,
                    'name' => $series->name,
                ])->values()->all(),
            ],
            [
                'label' => 'Events',
                'owner_type' => Event::class,
                'items' => Event::query()->orderBy('title')->get(['id', 'title'])->map(fn (Event $event) => [
                    'id' => $event->id,
                    'name' => $event->title,
                ])->values()->all(),
            ],
            [
                'label' => 'Beneficiaries',
                'owner_type' => Beneficiary::class,
                'items' => Beneficiary::query()->orderBy('name')->orderBy('surname')->get(['id', 'name', 'surname'])->map(fn (Beneficiary $beneficiary) => [
                    'id' => $beneficiary->id,
                    'name' => trim($beneficiary->name.' '.$beneficiary->surname),
                ])->values()->all(),
            ],
            [
                'label' => 'Stakeholders',
                'owner_type' => Stakeholder::class,
                'items' => Stakeholder::query()->orderBy('organization_name')->orderBy('name')->get(['id', 'organization_name', 'name'])->map(
                    fn (Stakeholder $stakeholder) => [
                        'id' => $stakeholder->id,
                        'name' => trim(($stakeholder->organization_name ? $stakeholder->organization_name.' - ' : '').$stakeholder->name),
                    ]
                )->values()->all(),
            ],
            [
                'label' => 'Assets',
                'owner_type' => Asset::class,
                'items' => Asset::query()->orderBy('name')->get(['id', 'name', 'asset_code'])->map(fn (Asset $asset) => [
                    'id' => $asset->id,
                    'name' => trim($asset->name.' '.($asset->asset_code ? '('.$asset->asset_code.')' : '')),
                ])->values()->all(),
            ],
            [
                'label' => 'Marketing Assets',
                'owner_type' => MarketingAsset::class,
                'items' => MarketingAsset::query()->orderByDesc('id')->get(['id', 'asset_file_name'])->map(fn (MarketingAsset $asset) => [
                    'id' => $asset->id,
                    'name' => $asset->asset_file_name ?: 'Marketing Asset #'.$asset->id,
                ])->values()->all(),
            ],
            [
                'label' => 'HR Departments',
                'owner_type' => StaffDepartment::class,
                'items' => StaffDepartment::query()->orderBy('name')->get(['id', 'name'])->map(fn (StaffDepartment $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ])->values()->all(),
            ],
            [
                'label' => 'Staff Members',
                'owner_type' => StaffMember::class,
                'items' => StaffMember::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn (StaffMember $member) => [
                    'id' => $member->id,
                    'name' => trim($member->first_name.' '.$member->last_name),
                ])->values()->all(),
            ],
        ])->filter(fn (array $group) => $this->accessService->canManageOwner($user, $group['owner_type']))
            ->values()
            ->all();
    }
}
