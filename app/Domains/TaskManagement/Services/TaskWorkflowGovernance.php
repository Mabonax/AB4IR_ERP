<?php

namespace App\Domains\TaskManagement\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Models\User;

class TaskWorkflowGovernance
{
    /**
     * @return array<int, string>
     */
    public function superUserRoles(): array
    {
        return ['super-admin', 'super admin'];
    }

    public function isSuperUser(User $user): bool
    {
        return $user->hasAnyRole($this->superUserRoles());
    }

    public function isOperationalManager(User $user): bool
    {
        if ($this->isSuperUser($user)) {
            return true;
        }

        $staff = $user->staffMember;

        return (bool) $staff && ((bool) $staff->is_manager || (bool) $staff->is_ceo);
    }

    public function managesProject(User $user, int|Project|null $project = null): bool
    {
        $staff = $user->staffMember;

        if (! $staff) {
            return false;
        }

        $projectModel = is_int($project)
            ? Project::query()->find($project)
            : $project;

        if (! $projectModel) {
            return false;
        }

        return (int) $projectModel->project_manager_id === (int) $staff->id;
    }

    public function canCreateDepartmentTask(User $user): bool
    {
        return $this->isOperationalManager($user);
    }

    public function canCreateProjectTask(User $user, int|Project|null $project = null): bool
    {
        return $this->isOperationalManager($user) || $this->managesProject($user, $project);
    }

    public function canRespondToTechnicalTickets(User $user): bool
    {
        return $this->isSuperUser($user) || $user->can('technical-tickets.respond');
    }

    public function belongsToTechnicalDepartment(User $user): bool
    {
        if ($this->isSuperUser($user)) {
            return true;
        }

        $departmentName = strtolower(trim((string) ($user->staffMember?->department?->name ?? '')));

        return $departmentName === 'technical';
    }

    public function isTechnicalManager(User $user): bool
    {
        if ($this->isSuperUser($user)) {
            return true;
        }

        return $this->canRespondToTechnicalTickets($user)
            && $this->belongsToTechnicalDepartment($user)
            && ((bool) $user->staffMember?->is_manager || (bool) $user->staffMember?->is_ceo);
    }

    public function canManageTechnicalTickets(User $user): bool
    {
        return $this->isTechnicalManager($user);
    }

    public function canViewTechnicalTicketQueue(User $user): bool
    {
        return $this->isTechnicalManager($user);
    }

    public function canWorkTechnicalTicket(User $user, SupportTicket $ticket): bool
    {
        if ($this->isTechnicalManager($user)) {
            return true;
        }

        if (! $this->canRespondToTechnicalTickets($user)) {
            return false;
        }

        return (int) ($ticket->assigned_to_user_id ?? 0) === (int) $user->id;
    }

    public function dashboardPersona(User $user): string
    {
        if ($this->isOperationalManager($user)) {
            return 'manager';
        }

        if ($this->canRespondToTechnicalTickets($user)) {
            return 'technical_responder';
        }

        return 'staff';
    }
}
