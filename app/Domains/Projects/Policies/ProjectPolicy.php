<?php

namespace App\Domains\Projects\Policies;

use App\Domains\Projects\Models\Project;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class ProjectPolicy
{
    use InteractsWithDomainPermissions;

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

    public function delete(User $user, Project $project): bool
    {
        return $this->canManageDomain($user, 'projects');
    }

    public function viewAttendanceSummary(User $user, Project $project): bool
    {
        return $this->canViewDomain($user, 'projects')
            || ((int) ($user->staffMember?->id ?? 0) === (int) $project->project_manager_id);
    }
}
