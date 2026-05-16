<?php

namespace App\Domains\Projects\Services;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectMilestoneAssessmentService
{
    public function storeAssessment(
        ProjectLocation $location,
        ProjectMilestone $milestone,
        array $validated,
        ?Facilitator $facilitator = null
    ): ProjectMilestoneAssessment {
        return DB::transaction(function () use ($location, $milestone, $validated, $facilitator) {
            if ($location->project?->status !== 'active') {
                throw ValidationException::withMessages([
                    'project_location_id' => 'Milestone assessments can only be created or corrected while the project is active.',
                ]);
            }

            if ((int) $milestone->project_id !== (int) $location->project_id) {
                throw ValidationException::withMessages([
                    'project_milestone_id' => 'Selected milestone does not belong to this location project.',
                ]);
            }

            $enrolled = ProjectEnrollment::query()
                ->where('project_id', $milestone->project_id)
                ->where('project_location_id', $location->id)
                ->where('beneficiary_id', $validated['beneficiary_id'])
                ->exists();

            if (! $enrolled) {
                throw ValidationException::withMessages([
                    'beneficiary_id' => 'Beneficiary is not enrolled at this location.',
                ]);
            }

            $maxScore = $milestone->max_score ?? 100;
            if ((int) $validated['score'] > $maxScore) {
                throw ValidationException::withMessages([
                    'score' => "Score cannot exceed the milestone maximum of {$maxScore}.",
                ]);
            }

            $passMark = (int) ceil($maxScore * 0.5);
            $status = (int) $validated['score'] >= $passMark ? 'completed' : 'failed';

            $existingAssessment = ProjectMilestoneAssessment::query()
                ->where('project_milestone_id', $milestone->id)
                ->where('beneficiary_id', $validated['beneficiary_id'])
                ->where('project_location_id', $location->id)
                ->first();

            if ($existingAssessment && $location->project?->status !== 'active') {
                throw ValidationException::withMessages([
                    'project_location_id' => 'Completed project delivery evidence is immutable after project closure.',
                ]);
            }

            return ProjectMilestoneAssessment::query()->updateOrCreate(
                [
                    'project_milestone_id' => $milestone->id,
                    'beneficiary_id' => $validated['beneficiary_id'],
                    'project_location_id' => $location->id,
                ],
                [
                    'status' => $status,
                    'score' => $validated['score'],
                    'comments' => $validated['comments'] ?? null,
                    'facilitator_id' => $facilitator?->id,
                    'assessed_at' => now(),
                ]
            );
        });
    }
}
