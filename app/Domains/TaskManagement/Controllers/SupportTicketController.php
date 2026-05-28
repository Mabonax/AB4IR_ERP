<?php

namespace App\Domains\TaskManagement\Controllers;

use App\Domains\Assets\Models\Asset;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Resources\SupportTicketResource;
use App\Domains\TaskManagement\Services\SupportTicketService;
use App\Domains\TaskManagement\Services\TaskWorkflowGovernance;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskManagement\AssignSupportTicketRequest;
use App\Http\Requests\TaskManagement\CloseSupportTicketRequest;
use App\Http\Requests\TaskManagement\ReopenSupportTicketRequest;
use App\Http\Requests\TaskManagement\ReplySupportTicketRequest;
use App\Http\Requests\TaskManagement\ResolveSupportTicketRequest;
use App\Http\Requests\TaskManagement\StoreSupportTicketRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function __construct(
        protected SupportTicketService $service,
        protected TaskWorkflowGovernance $governance,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupportTicket::class);

        $canManageQueue = $request->user() ? $this->governance->canManageTechnicalTickets($request->user()) : false;
        $perPage = (int) $request->integer('per_page', 15);
        $filters = $request->only([
            'status',
            'priority',
            'support_area',
            'assigned_to_user_id',
            'requester_user_id',
            'project_id',
            'program_id',
            'asset_id',
            'overdue',
            'search',
        ]);
        $tickets = $this->service->paginateForUser($request->user(), $filters, $perPage);
        $reportableAssets = Asset::query()
            ->with(['currentAssignment.department', 'currentAssignment.staffMember'])
            ->when(! $request->user()?->can('technical-tickets.respond'), function ($query) use ($request) {
                $staff = $request->user()?->staffMember;
                if (! $staff) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($builder) use ($staff) {
                    $builder->whereHas('currentAssignment', function ($assignmentQuery) use ($staff) {
                        $assignmentQuery->where('staff_member_id', $staff->id)
                            ->orWhere('department_id', $staff->department_id);
                    });
                });
            })
            ->orderBy('asset_code')
            ->get(['id', 'name', 'asset_code', 'asset_category_id', 'status']);

        return Inertia::render('TaskManagement/Tickets/Index', [
            'tickets' => SupportTicketResource::collection($tickets),
            'technicalResponders' => $canManageQueue ? $this->service->technicalResponders() : [],
            'requesters' => $canManageQueue
                ? \App\Models\User::query()->orderBy('name')->get(['id', 'name'])
                : $request->user()->newQuery()->whereKey($request->user()->id)->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'reportableAssets' => $reportableAssets,
            'filters' => $filters,
            'summary' => $this->service->dashboardSummary($request->user(), $filters),
            'can' => [
                'create' => $request->user()?->can('create', SupportTicket::class) ?? false,
                'respond' => $request->user() ? $this->governance->canRespondToTechnicalTickets($request->user()) : false,
                'manageQueue' => $canManageQueue,
            ],
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', SupportTicket::class);

        $this->service->createTicket($request->validated(), $request->user());

        return redirect()->route('task-management.tickets.index')
            ->with('success', 'Support ticket logged successfully.');
    }

    public function assign(AssignSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $this->service->assignTicket($ticket, (int) $request->validated()['assigned_to_user_id'], $request->user());

        return redirect()->route('task-management.tickets.index')
            ->with('success', 'Support ticket assigned.');
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $this->service->replyToTicket($ticket, $request->user(), $request->validated()['message']);

        return redirect()->route('task-management.tickets.index')
            ->with('success', 'Ticket response posted.');
    }

    public function resolve(ResolveSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('resolve', $ticket);

        $this->service->resolveTicket($ticket, $request->user(), $request->validated()['resolution_summary']);

        return redirect()->route('task-management.tickets.index')
            ->with('success', 'Support ticket resolved.');
    }

    public function close(CloseSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('close', $ticket);

        $this->service->closeTicket($ticket, $request->user(), $request->validated()['closing_notes'] ?? null);

        return redirect()->route('task-management.tickets.index')
            ->with('success', 'Support ticket closed.');
    }

    public function reopen(ReopenSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        $this->service->reopenTicket($ticket, $request->user(), $request->validated()['reason']);

        return redirect()->route('task-management.tickets.index')
            ->with('success', 'Support ticket reopened.');
    }
}
