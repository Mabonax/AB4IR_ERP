<?php

namespace App\Domains\Projects\Policies;

use App\Domains\Projects\Models\AttendanceRegister;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Policies\Concerns\InteractsWithProjectWorkflow;
use App\Models\User;

class AttendanceRegisterPolicy
{
    use InteractsWithProjectWorkflow;

    public function viewLocation(User $user, ProjectLocation $location): bool
    {
        return $this->hasProjectAdminAccess($user)
            || $this->isProjectManager($user, $location->project)
            || $this->isAssignedFacilitator($user, $location);
    }

    public function manageLocation(User $user, ProjectLocation $location): bool
    {
        if (! $this->projectAllowsOperationalDelivery($location->project)) {
            return false;
        }

        return $this->hasProjectManageAccess($user)
            || $this->isAssignedFacilitator($user, $location);
    }

    public function markHoliday(User $user, ProjectLocation $location): bool
    {
        if (! $this->projectAllowsOperationalDelivery($location->project)) {
            return false;
        }

        return $this->hasProjectManageAccess($user)
            || $this->isProjectManager($user, $location->project);
    }

    public function viewSummary(User $user, Project $project): bool
    {
        return $this->hasProjectAdminAccess($user)
            || $this->isProjectManager($user, $project);
    }

    public function export(User $user, AttendanceRegister $register): bool
    {
        $location = $register->location;

        return $location !== null && $this->viewLocation($user, $location);
    }
}
