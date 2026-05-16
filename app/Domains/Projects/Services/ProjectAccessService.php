<?php

namespace App\Domains\Projects\Services;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLocation;
use Illuminate\Support\Facades\Auth;

class ProjectAccessService
{
    public function hasAdminAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->hasAnyRole(['super-admin', 'super admin', 'admin']);
    }

    public function hasFullProjectAccess(): bool
    {
        $user = Auth::user();

        return (bool) $user?->can('domain.projects.view')
            || (bool) $user?->can('domain.projects.manage');
    }

    public function currentFacilitator(): ?Facilitator
    {
        $userId = Auth::id();
        if (! $userId) {
            return null;
        }

        $facilitator = Facilitator::query()->where('user_id', $userId)->first();
        if ($facilitator) {
            return $facilitator;
        }

        $email = Auth::user()?->email;
        if (! $email) {
            return null;
        }

        return Facilitator::query()->where('email', $email)->first();
    }

    public function currentFacilitatorOrAbort(string $message = 'No facilitator profile found for this account.'): Facilitator
    {
        $facilitator = $this->currentFacilitator();

        if (! $facilitator) {
            abort(403, $message);
        }

        return $facilitator;
    }

    public function canAccessAssignedLocation(ProjectLocation $location): bool
    {
        if ($this->hasAdminAccess() || $this->hasFullProjectAccess()) {
            return true;
        }

        $facilitator = $this->currentFacilitator();

        return $facilitator !== null && (int) $location->facilitator_id === (int) $facilitator->id;
    }

    public function assertAssignedLocationAccess(ProjectLocation $location, string $message): ?Facilitator
    {
        if ($this->hasAdminAccess() || $this->hasFullProjectAccess()) {
            return $location->facilitator;
        }

        $facilitator = $this->currentFacilitatorOrAbort($message);

        if ((int) $location->facilitator_id !== (int) $facilitator->id) {
            abort(403, $message);
        }

        return $facilitator;
    }

    public function isProjectManager(Project $project): bool
    {
        $user = Auth::user();
        $staffId = $user?->staffMember?->id;

        return $staffId !== null && (int) $project->project_manager_id === (int) $staffId;
    }

    public function assertProjectSummaryAccess(Project $project, string $message): void
    {
        if ($this->hasAdminAccess() || $this->isProjectManager($project)) {
            return;
        }

        abort(403, $message);
    }
}
