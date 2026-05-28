<?php

namespace App\Domains\Projects\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ProjectEnrollmentConsistencyService
{
    public const BENEFICIARY_ASSIGNABLE_STATUSES = ['planned', 'active', 'on_hold'];

    public function assertLocationBelongsToProject(int $projectId, int $projectLocationId): ProjectLocation
    {
        $location = ProjectLocation::query()->find($projectLocationId);

        if (! $location) {
            throw ValidationException::withMessages([
                'project_location_id' => ['Selected project location does not exist.'],
            ]);
        }

        if ((int) $location->project_id !== $projectId) {
            throw ValidationException::withMessages([
                'project_location_id' => ['Selected project location does not belong to the chosen project.'],
            ]);
        }

        return $location;
    }

    public function assertProjectAcceptsBeneficiaryPlacement(int $projectId, ?int $currentProjectId = null): void
    {
        $project = \App\Domains\Projects\Models\Project::query()->find($projectId);

        if (! $project) {
            throw ValidationException::withMessages([
                'project_id' => ['Selected project does not exist.'],
            ]);
        }

        if ((int) $project->id === (int) $currentProjectId) {
            return;
        }

        if (in_array((string) $project->status, self::BENEFICIARY_ASSIGNABLE_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'project_id' => ['Beneficiaries can only be added to planned, active, or on-hold projects.'],
        ]);
    }

    public function assertBeneficiaryBelongsToProject(Beneficiary $beneficiary, int $projectId): void
    {
        if ((int) $beneficiary->project_id !== $projectId) {
            throw ValidationException::withMessages([
                'beneficiary_id' => ['Beneficiary is not assigned to the selected project.'],
            ]);
        }
    }

    public function syncBeneficiaryEnrollment(
        Beneficiary $beneficiary,
        int $projectId,
        int $projectLocationId,
        string $status,
        string|Carbon|null $enrolledAt = null,
        ?int $currentProjectId = null
    ): ProjectEnrollment {
        $this->assertProjectAcceptsBeneficiaryPlacement($projectId, $currentProjectId);
        $this->assertLocationBelongsToProject($projectId, $projectLocationId);

        ProjectEnrollment::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('project_id', '!=', $projectId)
            ->where('status', 'enrolled')
            ->update([
                'status' => 'dropped',
                'updated_at' => now(),
            ]);

        return ProjectEnrollment::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'beneficiary_id' => $beneficiary->id,
            ],
            [
                'project_location_id' => $projectLocationId,
                'status' => $status,
                'enrolled_at' => $enrolledAt ? Carbon::parse($enrolledAt) : now(),
            ]
        );
    }
}
