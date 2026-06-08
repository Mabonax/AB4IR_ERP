<?php

namespace App\Domains\Marketing\Controllers;

use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Enums\MarketingDeliverableType;
use App\Domains\Marketing\Enums\MarketingOperationalUnit;
use App\Domains\Marketing\Models\MarketingAsset;
use App\Domains\Marketing\Models\MarketingDeliverable;
use App\Domains\Marketing\Models\MarketingRequest;
use App\Domains\Marketing\Resources\MarketingAssetResource;
use App\Domains\Marketing\Resources\MarketingRequestResource;
use App\Domains\Marketing\Resources\PublicationRecordResource;
use App\Domains\Marketing\Services\MarketingOperationsService;
use App\Domains\Organization\Enums\OrganizationDocumentType;
use App\Domains\Organization\Enums\OrganizationDocumentSlot;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Domains\Organization\Services\OrganizationDocumentVaultService;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\ImportMarketingMetricSnapshotRequest;
use App\Http\Requests\Marketing\ReviewMarketingDeliverableRequest;
use App\Http\Requests\Marketing\StoreDeliverableVersionRequest;
use App\Http\Requests\Marketing\StoreMarketingRequestRequest;
use App\Http\Requests\Marketing\StoreMarketingRequestCommentRequest;
use App\Http\Requests\Marketing\StorePublicationRecordRequest;
use App\Http\Requests\Marketing\UpdateMarketingRequestRequest;
use App\Http\Requests\Marketing\UploadMarketingRequestDocumentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MarketingOperationsController extends Controller
{
    public function __construct(
        protected MarketingOperationsService $service,
        protected OrganizationDocumentVaultService $vaultService,
    ) {}

    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', MarketingRequest::class);

        return Inertia::render('Marketing/Dashboard', [
            'dashboard' => $this->service->dashboard($request->user()),
        ]);
    }

    public function requestsIndex(Request $request): Response
    {
        $this->authorize('viewAny', MarketingRequest::class);

        $filters = $request->only(['status', 'priority', 'search']);

        return Inertia::render('Marketing/Requests/Index', [
            'requests' => MarketingRequestResource::collection(
                $this->service->paginateRequests($request->user(), $filters, (int) $request->integer('per_page', 15))
            ),
            'filters' => $filters,
            'can' => [
                'create' => $request->user()?->can('create', MarketingRequest::class) ?? false,
            ],
        ]);
    }

    public function createRequest(Request $request): Response
    {
        $this->authorize('create', MarketingRequest::class);

        return Inertia::render('Marketing/Requests/Create', [
            'events' => Event::query()->orderBy('title')->get(['id', 'title']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
            'approvers' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'assignees' => User::query()
                ->whereHas('staffMember.department', fn ($query) => $query->where('name', 'marketing'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'deliverableTypes' => MarketingDeliverableType::values(),
            'units' => MarketingOperationalUnit::values(),
        ]);
    }

    public function storeRequest(StoreMarketingRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', MarketingRequest::class);

        $marketingRequest = $this->service->createRequest($request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $marketingRequest)
            ->with('success', 'Marketing request created with deliverables.');
    }

    public function showRequest(Request $request, MarketingRequest $marketingRequest): Response
    {
        $this->authorize('view', $marketingRequest);

        return Inertia::render('Marketing/Requests/Show', [
            'requestRecord' => MarketingRequestResource::make($this->service->loadRequest($marketingRequest))->resolve(),
            'events' => Event::query()->orderBy('title')->get(['id', 'title']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
            'approvers' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'assignees' => User::query()
                ->whereHas('staffMember.department', fn ($query) => $query->where('name', 'marketing'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'units' => MarketingOperationalUnit::values(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'documentTypes' => OrganizationDocumentType::options(),
            'slotOptions' => OrganizationDocumentSlot::options(),
            'canManageVault' => $request->user()?->can('create', OrganizationDocument::class) ?? false,
        ]);
    }

    public function updateRequest(UpdateMarketingRequestRequest $request, MarketingRequest $marketingRequest): RedirectResponse
    {
        $this->authorize('update', $marketingRequest);

        $this->service->updateRequest($marketingRequest, $request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $marketingRequest)
            ->with('success', 'Marketing request updated.');
    }

    public function workspace(Request $request): Response
    {
        $this->authorize('viewAny', MarketingRequest::class);

        return Inertia::render('Marketing/Deliverables/Workspace', [
            'workspace' => $this->service->workspace($request->user()),
        ]);
    }

    public function approvals(Request $request): Response
    {
        $this->authorize('viewAny', MarketingRequest::class);

        return Inertia::render('Marketing/Approvals/Index', [
            'approvalQueue' => $this->service->approvals($request->user()),
        ]);
    }

    public function assets(Request $request): Response
    {
        $this->authorize('viewAny', MarketingAsset::class);

        return Inertia::render('Marketing/Assets/Index', [
            'assets' => MarketingAssetResource::collection($this->service->assetLibrary($request->user())),
        ]);
    }

    public function publications(Request $request): Response
    {
        $this->authorize('viewAny', MarketingRequest::class);

        return Inertia::render('Marketing/Publications/Index', [
            'publications' => PublicationRecordResource::collection($this->service->publicationRegister($request->user())),
        ]);
    }

    public function comment(StoreMarketingRequestCommentRequest $request, MarketingRequest $marketingRequest): RedirectResponse
    {
        $this->authorize('comment', $marketingRequest);

        $this->service->addComment($marketingRequest, $request->user(), $request->validated()['message']);

        return redirect()->route('marketing.requests.show', $marketingRequest)
            ->with('success', 'Comment added to the request.');
    }

    public function uploadDocument(UploadMarketingRequestDocumentRequest $request, MarketingRequest $marketingRequest): RedirectResponse
    {
        $this->authorize('uploadDocument', $marketingRequest);

        $this->service->uploadRequestDocument($marketingRequest, $request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $marketingRequest)
            ->with('success', 'Request document uploaded.');
    }

    public function downloadDocument(Request $request, MarketingRequest $marketingRequest, \App\Domains\Marketing\Models\MarketingRequestDocument $document): HttpResponse
    {
        $this->authorize('view', $marketingRequest);
        abort_unless((int) $document->marketing_request_id === (int) $marketingRequest->id, 404);

        return $this->service->downloadRequestDocument($document);
    }

    public function storeVersion(StoreDeliverableVersionRequest $request, MarketingDeliverable $deliverable): RedirectResponse
    {
        $this->authorize('uploadVersion', $deliverable);

        $this->service->uploadVersion($deliverable, $request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $deliverable->request_id)
            ->with('success', 'Deliverable version uploaded.');
    }

    public function approveDeliverable(ReviewMarketingDeliverableRequest $request, MarketingDeliverable $deliverable): RedirectResponse
    {
        $this->authorize('approve', $deliverable);

        $this->service->approveDeliverable($deliverable, $request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $deliverable->request_id)
            ->with('success', 'Deliverable approved.');
    }

    public function requestDeliverableChanges(ReviewMarketingDeliverableRequest $request, MarketingDeliverable $deliverable): RedirectResponse
    {
        $this->authorize('approve', $deliverable);

        $this->service->requestAmendments($deliverable, $request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $deliverable->request_id)
            ->with('success', 'Changes requested for deliverable.');
    }

    public function publishAsset(StorePublicationRecordRequest $request, MarketingAsset $asset): RedirectResponse
    {
        $this->authorize('publish', $asset);

        $this->service->publishAsset($asset, $request->validated(), $request->user());

        return redirect()->route('marketing.requests.show', $asset->deliverable->request_id)
            ->with('success', 'Asset published and metrics recorded.');
    }

    public function archiveAsset(Request $request, MarketingAsset $asset): RedirectResponse
    {
        $this->authorize('archive', $asset);

        $this->service->archiveAsset($asset, $request->user());

        return redirect()->route('marketing.requests.show', $asset->deliverable->request_id)
            ->with('success', 'Asset archived.');
    }

    public function importMetrics(ImportMarketingMetricSnapshotRequest $request): RedirectResponse
    {
        abort_unless($this->service->canImportMetrics($request->user()), 403);

        $count = $this->service->importMetricSnapshots($request->file('file'), $request->user());

        return redirect()->route('marketing.publications.index')
            ->with('success', sprintf('%d metric snapshot row(s) imported.', $count));
    }

    public function publishAssetToVault(Request $request, MarketingAsset $asset): RedirectResponse
    {
        $this->authorize('create', OrganizationDocument::class);
        $this->authorize('publish', $asset);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in(OrganizationDocumentType::values())],
            'description' => ['nullable', 'string', 'max:4000'],
            'audience_scope' => ['required', 'in:all_staff,department,selected_users'],
            'department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
            'slot_key' => ['nullable', Rule::in(OrganizationDocumentSlot::values())],
            'replace_existing' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'selected_user_ids' => ['nullable', 'array'],
            'selected_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $this->vaultService->publishFromMarketingAsset($asset, $data, $request->user());

        return redirect()->route('marketing.requests.show', $asset->deliverable->request_id)
            ->with('success', 'Approved marketing asset published to the organization vault.');
    }
}
