<?php

namespace App\Domains\TaskManagement\Controllers;

use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Services\SupportTicketService;
use App\Domains\TaskManagement\Services\TaskWorkflowGovernance;
use App\Domains\TaskManagement\Services\WorkTaskService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskManagementDashboardController extends Controller
{
    public function __construct(
        protected WorkTaskService $taskService,
        protected SupportTicketService $ticketService,
        protected TaskWorkflowGovernance $governance,
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', WorkTask::class);

        return Inertia::render('TaskManagement/Dashboard', [
            'dashboard' => [
                'persona' => $this->governance->dashboardPersona($request->user()),
                'can_create_task' => $this->governance->canCreateDepartmentTask($request->user()),
                'can_respond' => $this->governance->canRespondToTechnicalTickets($request->user()),
                'tasks' => $this->taskService->operationalDashboard($request->user()),
                'tickets' => $this->ticketService->operationalDashboard($request->user()),
            ],
        ]);
    }
}
