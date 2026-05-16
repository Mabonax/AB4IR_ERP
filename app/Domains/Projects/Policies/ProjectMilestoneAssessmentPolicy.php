<?php

namespace App\Domains\Projects\Policies;

use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Projects\Policies\Concerns\InteractsWithProjectWorkflow;
use App\Models\User;

class ProjectMilestoneAssessmentPolicy
{
    use InteractsWithProjectWorkflow;

    public function store(User $user, ProjectLocation $location): bool
    {
        if (! $this->projectAllowsOperationalDelivery($location->project)) {
            return false;
        }

        return $this->hasProjectManageAccess($user)
            || $this->isAssignedFacilitator($user, $location);
    }

    public function update(User $user, ProjectMilestoneAssessment $assessment): bool
    {
        $location = $assessment->location;

        return $location !== null && $this->store($user, $location);
    }
}
