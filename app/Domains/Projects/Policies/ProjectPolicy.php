<?php

namespace App\Domains\Projects\Policies;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Policies\Concerns\InteractsWithProjectWorkflow;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class ProjectPolicy
{
    use InteractsWithDomainPermissions;
    use InteractsWithProjectWorkflow {
        InteractsWithProjectWorkflow::canViewDomain insteadof InteractsWithDomainPermissions;
        InteractsWithProjectWorkflow::canManageDomain insteadof InteractsWithDomainPermissions;
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'projects');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->canViewDomain($user, 'projects');
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->canManageDomain($user, 'projects');
    }

    public function attachMilestones(User $user, Project $project): bool
    {
        if ($this->canManageDomain($user, 'projects') || $this->isProjectManager($user, $project)) {
            return true;
        }

        $project->loadMissing('locations.facilitator');

        return $project->locations->contains(fn ($location) => $this->isAssignedFacilitator($user, $location));
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->canManageDomain($user, 'projects');
    }

    public function conclude(User $user, Project $project): bool
    {
        return $this->canManageDomain($user, 'projects')
            || ((int) ($user->staffMember?->id ?? 0) === (int) $project->project_manager_id);
    }

    public function createReport(User $user, Project $project): bool
    {
        return $this->canManageDomain($user, 'projects')
            || ((int) ($user->staffMember?->id ?? 0) === (int) $project->project_manager_id);
    }

    public function viewReport(User $user, Project $project): bool
    {
        return $this->canViewDomain($user, 'projects')
            || ((int) ($user->staffMember?->id ?? 0) === (int) $project->project_manager_id);
    }

    public function viewAttendanceSummary(User $user, Project $project): bool
    {
        return $this->canViewDomain($user, 'projects')
            || ((int) ($user->staffMember?->id ?? 0) === (int) $project->project_manager_id);
    }
}
