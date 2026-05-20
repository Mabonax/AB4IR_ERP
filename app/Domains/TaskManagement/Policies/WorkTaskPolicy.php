<?php

namespace App\Domains\TaskManagement\Policies;

use App\Domains\TaskManagement\Models\WorkTask;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class WorkTaskPolicy
{
    use InteractsWithDomainPermissions;

    protected function managedProjectIds(User $user): array
    {
        $staffId = (int) ($user->staffMember?->id ?? 0);

        if ($staffId <= 0) {
            return [];
        }

        return \App\Domains\Projects\Models\Project::query()
            ->where('project_manager_id', $staffId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function directReportUserIds(User $user): array
    {
        $staff = $user->staffMember;

        if (! $staff) {
            return [];
        }

        return $staff->directReports()
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'task-management');
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'task-management');
    }

    public function view(User $user, WorkTask $task): bool
    {
        if (! $this->canViewDomain($user, 'task-management')) {
            return false;
        }

        $departmentId = (int) ($user->staffMember?->department_id ?? 0);
        $managedProjectIds = $this->managedProjectIds($user);
        $directReportUserIds = $this->directReportUserIds($user);

        return (int) $task->creator_user_id === (int) $user->id
            || (int) ($task->assigned_to_user_id ?? 0) === (int) $user->id
            || ($departmentId > 0 && (int) ($task->assigned_department_id ?? 0) === $departmentId)
            || in_array((int) ($task->assigned_to_user_id ?? 0), $directReportUserIds, true)
            || in_array((int) ($task->project_id ?? 0), $managedProjectIds, true);
    }

    public function updateStatus(User $user, WorkTask $task): bool
    {
        return $this->view($user, $task);
    }

    public function comment(User $user, WorkTask $task): bool
    {
        return $this->view($user, $task);
    }

    public function reassign(User $user, WorkTask $task): bool
    {
        return $this->canManageDomain($user, 'task-management') && $this->view($user, $task);
    }
}
