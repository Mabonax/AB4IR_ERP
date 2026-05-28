<?php

namespace App\Domains\Marketing\Controllers;

use App\Domains\Events\Models\Event;
use App\Domains\Marketing\Models\MarketingJob;
use App\Domains\Marketing\Models\MarketingJobDocument;
use App\Domains\Marketing\Resources\MarketingJobResource;
use App\Domains\Marketing\Services\MarketingService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\ReassignMarketingJobRequest;
use App\Http\Requests\Marketing\ReviewMarketingJobRequest;
use App\Http\Requests\Marketing\StoreMarketingJobCommentRequest;
use App\Http\Requests\Marketing\StoreMarketingJobRequest;
use App\Http\Requests\Marketing\SubmitMarketingJobApprovalRequest;
use App\Http\Requests\Marketing\UpdateMarketingJobStatusRequest;
use App\Http\Requests\Marketing\UploadMarketingJobDocumentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MarketingController extends Controller
{
    public function __construct(
        protected MarketingService $service,
    ) {}

    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', MarketingJob::class);

        return Inertia::render('Marketing/Dashboard', [
            'dashboard' => $this->service->dashboard($request->user()),
        ]);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MarketingJob::class);

        $perPage = (int) $request->integer('per_page', 15);
        $filters = $request->only(['status', 'priority', 'job_type', 'event_id', 'assignee_user_id', 'search']);
        $jobs = $this->service->paginateForUser($request->user(), $filters, $perPage);

        return Inertia::render('Marketing/Index', [
            'jobs' => MarketingJobResource::collection($jobs),
            'events' => Event::query()->orderBy('title')->get(['id', 'title']),
            'assignees' => User::query()
                ->whereHas('staffMember.department', fn ($query) => $query->where('name', 'marketing'))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'filters' => $filters,
            'summary' => $this->service->summary($request->user(), $filters),
            'can' => [
                'create' => $request->user()?->can('create', MarketingJob::class) ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', MarketingJob::class);

        return Inertia::render('Marketing/Create', [
            'events' => Event::query()->orderBy('title')->get(['id', 'title']),
            'assignees' => User::query()
                ->whereHas('staffMember.department', fn ($query) => $query->where('name', 'marketing'))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, MarketingJob $job): Response
    {
        $this->authorize('view', $job);

        $job->load([
            'event:id,title',
            'creator:id,name,email',
            'assignee:id,name,email',
            'submittedBy:id,name,email',
            'reviewedBy:id,name,email',
            'closedBy:id,name,email',
            'creatorDepartment:id,name',
            'assignedDepartment:id,name',
            'documents.uploader:id,name',
            'comments.user:id,name',
            'history.actor:id,name',
        ]);

        return Inertia::render('Marketing/Show', [
            'job' => MarketingJobResource::make($job)->resolve(),
            'assignees' => User::query()
                ->whereHas('staffMember.department', fn ($query) => $query->where('name', 'marketing'))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMarketingJobRequest $request): RedirectResponse
    {
        $this->authorize('create', MarketingJob::class);

        $job = $this->service->createJob($request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing work item created.');
    }

    public function updateStatus(UpdateMarketingJobStatusRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('updateStatus', $job);

        $this->service->updateStatus($job, $request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing work status updated.');
    }

    public function submitForApproval(SubmitMarketingJobApprovalRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('submitForApproval', $job);

        $this->service->submitForApproval($job, $request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing work submitted for approval.');
    }

    public function approve(ReviewMarketingJobRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('approve', $job);

        $this->service->approve($job, $request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing work approved and closed.');
    }

    public function requestAmendments(ReviewMarketingJobRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('requestAmendments', $job);

        $this->service->requestAmendments($job, $request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing work returned for amendments.');
    }

    public function comment(StoreMarketingJobCommentRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('comment', $job);

        $this->service->addComment($job, $request->user(), $request->validated()['message']);

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing comment added.');
    }

    public function reassign(ReassignMarketingJobRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('reassign', $job);

        $this->service->reassign($job, $request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing work reassigned.');
    }

    public function uploadDocument(UploadMarketingJobDocumentRequest $request, MarketingJob $job): RedirectResponse
    {
        $this->authorize('uploadDocument', $job);

        $this->service->uploadDocument($job, $request->validated(), $request->user());

        return redirect()->route('marketing.jobs.show', $job)
            ->with('success', 'Marketing document uploaded.');
    }

    public function downloadProof(Request $request, MarketingJob $job): HttpResponse
    {
        $this->authorize('view', $job);

        return $this->service->downloadProof($job);
    }

    public function downloadDocument(Request $request, MarketingJob $job, MarketingJobDocument $document): HttpResponse
    {
        $this->authorize('view', $job);
        abort_unless((int) $document->marketing_job_id === (int) $job->id, 404);

        return $this->service->downloadDocument($document);
    }
}
