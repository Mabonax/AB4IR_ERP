<?php

namespace App\Http\Controllers;

use App\Domains\Assets\Services\AssetService;
use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Leave\Services\LeaveManagementService;
use App\Domains\Marketing\Services\MarketingService;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Services\StaffAttendanceService;
use App\Domains\TaskManagement\Services\SupportTicketService;
use App\Domains\TaskManagement\Services\TaskWorkflowGovernance;
use App\Domains\TaskManagement\Services\WorkTaskService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected WorkTaskService $taskService,
        protected SupportTicketService $ticketService,
        protected MarketingService $marketingService,
        protected TaskWorkflowGovernance $governance,
        protected LeaveManagementService $leaveManagementService,
        protected AssetService $assetService,
        protected StaffAttendanceService $staffAttendanceService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard', [
            'dashboard' => [
                'tasks' => $this->taskSection($user),
                'tickets' => $this->ticketSection($user),
                'secondary' => $this->secondaryWidgets($user),
            ],
        ]);
    }

    protected function taskSection(User $user): array
    {
        $canViewTasks = $user->can('domain.task-management.view') || $user->can('domain.task-management.manage');

        if (! $canViewTasks) {
            return [
                'available' => false,
                'can_create' => false,
                'persona' => $this->governance->dashboardPersona($user),
                'summary' => [
                    'total' => 0,
                    'assigned_to_me' => 0,
                    'created_by_me' => 0,
                    'overdue' => 0,
                    'unassigned_queue' => 0,
                ],
                'assigned' => [],
                'created' => [],
                'overdue' => [],
                'queue' => [],
                'href' => route('task-management.tasks.index'),
            ];
        }

        return array_merge(
            [
                'available' => true,
                'can_create' => $this->governance->canCreateDepartmentTask($user),
                'persona' => $this->governance->dashboardPersona($user),
                'href' => route('task-management.tasks.index'),
            ],
            $this->taskService->homeDashboard($user)
        );
    }

    protected function ticketSection(User $user): array
    {
        return array_merge(
            [
                'available' => true,
                'can_respond' => $this->governance->canRespondToTechnicalTickets($user),
                'persona' => $this->governance->dashboardPersona($user),
                'href' => route('task-management.tickets.index'),
            ],
            $this->ticketService->homeDashboard($user)
        );
    }

    protected function secondaryWidgets(User $user): array
    {
        return collect([
            $this->leaveWidget($user),
            $this->marketingWidget($user),
            $this->projectsWidget($user),
            $this->assetsWidget($user),
            $this->staffWidget($user),
            $this->attendanceWidget($user),
        ])->filter()->values()->all();
    }

    protected function leaveWidget(User $user): ?array
    {
        $staff = $user->staffMember;
        $canManageLeave = $user->can('domain.leave.manage');
        $canManageHr = $user->can('domain.human-resources.manage');
        $canViewLeave = $user->can('domain.leave.view') || $canManageLeave;
        $canViewHr = $user->can('domain.human-resources.view') || $canManageHr;

        if (! $canViewLeave && ! $canViewHr) {
            return null;
        }

        $managerPending = ($canManageLeave && $staff)
            ? LeaveRequest::query()
                ->where('manager_id', $staff->id)
                ->where('status', 'submitted')
                ->count()
            : 0;

        $hrPending = $canManageHr
            ? LeaveRequest::query()
                ->where('status', 'manager_approved')
                ->count()
            : 0;

        $myPending = $staff && $canViewLeave
            ? LeaveRequest::query()
                ->where('staff_member_id', $staff->id)
                ->whereIn('status', ['submitted', 'manager_approved'])
                ->count()
            : 0;

        return [
            'key' => 'leave',
            'title' => 'Leave workflow',
            'value' => $managerPending + $hrPending + $myPending,
            'description' => $canManageHr
                ? "{$hrPending} waiting for HR and {$managerPending} waiting for manager review."
                : ($canManageLeave
                    ? "{$managerPending} team requests need your review."
                    : "{$myPending} leave requests are still in progress."),
            'href' => $canViewHr ? route('human-resources.dashboard') : route('leave-requests.index'),
        ];
    }

    protected function projectsWidget(User $user): ?array
    {
        $canViewProjects = $user->can('domain.projects.view') || $user->can('domain.projects.manage');
        $canViewActivities = $user->can('project-activities.view');

        if (! $canViewProjects && ! $canViewActivities) {
            return null;
        }

        if ($canViewProjects) {
            $activeProjects = Project::query()->where('status', 'active')->count();
            $plannedProjects = Project::query()->where('status', 'planned')->count();

            return [
                'key' => 'projects',
                'title' => 'Project delivery',
                'value' => $activeProjects,
                'description' => "{$plannedProjects} planned projects and ".ProjectLocation::query()->count().' delivery locations are in the portfolio.',
                'href' => route('projects.dashboard'),
            ];
        }

        $locationCount = ProjectLocation::query()
            ->whereHas('facilitator', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        $beneficiaryCount = ProjectEnrollment::query()
            ->whereHas('location.facilitator', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return [
            'key' => 'project-activities',
            'title' => 'Delivery locations',
            'value' => $locationCount,
            'description' => "{$beneficiaryCount} beneficiaries are attached to your assigned locations.",
            'href' => route('project-locations.dashboard'),
        ];
    }

    protected function marketingWidget(User $user): ?array
    {
        $canViewMarketing = $user->can('domain.marketing.view') || $user->can('domain.marketing.manage');

        if (! $canViewMarketing) {
            return null;
        }

        $dashboard = $this->marketingService->dashboard($user);
        $summary = $dashboard['summary'] ?? [];

        return [
            'key' => 'marketing',
            'title' => 'Marketing approvals',
            'value' => (int) ($summary['pending_approval'] ?? 0),
            'description' => sprintf(
                '%d awaiting approval, %d returned for amendments, %d active in production.',
                (int) ($summary['pending_approval'] ?? 0),
                (int) ($summary['changes_requested'] ?? 0),
                (int) (($summary['open'] ?? 0) + ($summary['in_progress'] ?? 0))
            ),
            'href' => route('marketing.dashboard'),
        ];
    }

    protected function assetsWidget(User $user): ?array
    {
        if (! $user->can('domain.assets.view') && ! $user->can('domain.assets.manage')) {
            return null;
        }

        $data = $this->assetService->managerDashboardData();
        $stats = $data['stats'] ?? [];

        return [
            'key' => 'assets',
            'title' => 'Asset portfolio',
            'value' => (int) ($stats['portfolioAssets'] ?? 0),
            'description' => sprintf(
                '%d in maintenance, %d retired, %d currently assigned.',
                (int) ($stats['maintenanceAssets'] ?? 0),
                (int) ($stats['retiredAssets'] ?? 0),
                (int) ($stats['staffAssets'] ?? 0)
            ),
            'href' => route('assets.manager-dashboard'),
        ];
    }

    protected function staffWidget(User $user): ?array
    {
        $canViewStaff = $user->can('domain.staff.view') || $user->can('domain.staff.manage');
        $canViewHr = $user->can('domain.human-resources.view') || $user->can('domain.human-resources.manage');

        if (! $canViewStaff) {
            return null;
        }

        $managerSummary = $this->leaveManagementService->managerDashboardSummary($user->staffMember);

        return [
            'key' => 'staff',
            'title' => 'People overview',
            'value' => (int) ($managerSummary['team_members'] ?? 0),
            'description' => $managerSummary['team_members'] > 0
                ? "{$managerSummary['pending_approvals']} leave approvals are waiting across your reporting line."
                : StaffMember::query()->where('status', 'active')->count().' active staff are in the organisation.',
            'href' => $canViewHr ? route('human-resources.dashboard') : route('staff.index'),
        ];
    }

    protected function attendanceWidget(User $user): ?array
    {
        return $this->staffAttendanceService->dashboardWidget($user);
    }
}
