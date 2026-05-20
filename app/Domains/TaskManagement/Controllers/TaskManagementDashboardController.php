<?php

namespace App\Domains\TaskManagement\Controllers;

use App\Domains\TaskManagement\Models\WorkTask;
use App\Domains\TaskManagement\Services\SupportTicketService;
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
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', WorkTask::class);

        return Inertia::render('TaskManagement/Dashboard', [
            'dashboard' => [
                'tasks' => $this->taskService->operationalDashboard($request->user()),
                'tickets' => $this->ticketService->operationalDashboard($request->user()),
            ],
        ]);
    }
}
