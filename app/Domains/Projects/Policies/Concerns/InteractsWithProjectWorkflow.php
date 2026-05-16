<?php

namespace App\Domains\Projects\Policies\Concerns;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

trait InteractsWithProjectWorkflow
{
    use InteractsWithDomainPermissions;

    protected function hasProjectAdminAccess(User $user): bool
    {
        return $this->canViewDomain($user, 'projects');
    }

    protected function hasProjectManageAccess(User $user): bool
    {
        return $this->canManageDomain($user, 'projects');
    }

    protected function isProjectManager(User $user, Project $project): bool
    {
        $staffId = $user->staffMember?->id;

        return $staffId !== null && (int) $project->project_manager_id === (int) $staffId;
    }

    protected function isAssignedFacilitator(User $user, ProjectLocation $location): bool
    {
        $facilitatorId = $user->facilitator?->id;

        if ($facilitatorId !== null) {
            return (int) $location->facilitator_id === (int) $facilitatorId;
        }

        $email = $user->email;
        if (! $email) {
            return false;
        }

        return $location->facilitator !== null
            && strcasecmp((string) $location->facilitator->email, (string) $email) === 0;
    }

    protected function projectAllowsOperationalDelivery(Project $project): bool
    {
        return in_array($project->status, ['active'], true);
    }
}
