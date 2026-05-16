<?php

namespace App\Domains\BusinessDevelopment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BdsPitchSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = (int) ($request->user()?->id ?? 0);
        $panelistCount = (int) $this->panelists->count();
        $prospectCount = (int) $this->prospects->count();
        $expectedScorecards = $panelistCount * $prospectCount;
        $submittedScorecards = (int) $this->assessments
            ->where('status', 'submitted')
            ->count();
        $decidedProspects = (int) $this->prospects
            ->filter(fn ($prospect) => filled($prospect->manager_decision))
            ->count();
        $consolidatedProspects = (int) $this->prospects
            ->filter(fn ($prospect) => (int) $prospect->submitted_assessments_count >= $panelistCount)
            ->count();

        $hasChair = $this->panelists->contains(fn ($panelist) => (bool) $panelist->is_chair);

        $workflowBlockers = [];

        if ($panelistCount < 2) {
            $workflowBlockers[] = 'At least two panelists are required before the session can begin.';
        }

        if (! $hasChair) {
            $workflowBlockers[] = 'A chair panelist must be assigned before the session can begin.';
        }

        if ($prospectCount < 1) {
            $workflowBlockers[] = 'At least one prospect must be attached before the session can begin.';
        }

        if ($submittedScorecards < $expectedScorecards && $this->status !== 'scheduled') {
            $workflowBlockers[] = 'All invited panelists must submit scorecards before consolidation and approval.';
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'scheduled_for' => $this->scheduled_for?->toDateTimeString(),
            'venue' => $this->venue,
            'expected_prospect_count' => $this->expected_prospect_count,
            'notes' => $this->notes,
            'status' => $this->status,
            'status_label' => str($this->status)->replace('_', ' ')->title()->value(),
            'started_at' => $this->started_at?->toDateTimeString(),
            'consolidated_at' => $this->consolidated_at?->toDateTimeString(),
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'created_by' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'approved_by' => [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ],
            'workflow' => [
                'has_chair' => $hasChair,
                'is_fully_submitted' => $submittedScorecards >= $expectedScorecards && $expectedScorecards > 0,
                'allowed_transitions' => [
                    'can_start' => $this->status === 'scheduled' && empty($workflowBlockers),
                    'can_consolidate' => in_array($this->status, ['in_progress', 'consolidated', 'approved'], true),
                    'can_approve' => $consolidatedProspects > 0,
                ],
                'blockers' => $workflowBlockers,
            ],
            'summary' => [
                'panelists_total' => $panelistCount,
                'prospects_total' => $prospectCount,
                'scorecards_expected' => $expectedScorecards,
                'scorecards_submitted' => $submittedScorecards,
                'scorecards_pending' => max($expectedScorecards - $submittedScorecards, 0),
                'consolidated_prospects' => $consolidatedProspects,
                'decided_prospects' => $decidedProspects,
                'ready_to_start' => $this->status === 'scheduled' && empty($workflowBlockers),
                'ready_to_consolidate' => $submittedScorecards >= $expectedScorecards && $expectedScorecards > 0,
                'ready_for_final_approval' => $consolidatedProspects > 0,
            ],
            'panelists' => $this->panelists
                ->sortBy([
                    ['is_chair', 'desc'],
                    ['id', 'asc'],
                ])
                ->values()
                ->map(function ($panelist) {
                    return [
                        'id' => $panelist->id,
                        'user_id' => $panelist->user_id,
                        'name' => $panelist->user?->name,
                        'email' => $panelist->user?->email,
                        'panel_role' => $panelist->panel_role,
                        'is_chair' => (bool) $panelist->is_chair,
                    ];
                }),
            'prospects' => $this->prospects
                ->sortBy('sequence_number')
                ->values()
                ->map(function ($prospect) use ($currentUserId, $panelistCount) {
                    $prospectAssessments = $this->assessments
                        ->where('smme_id', $prospect->bds_application_id)
                        ->values();
                    $submittedAssessments = $prospectAssessments
                        ->where('status', 'submitted')
                        ->values();
                    $actualSubmittedCount = $submittedAssessments->count();

                    $submittedJudgeIds = $submittedAssessments
                        ->pluck('judge_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique();

                    $missingPanelists = $this->panelists
                        ->filter(fn ($panelist) => ! $submittedJudgeIds->contains((int) $panelist->user_id))
                        ->values();

                    return [
                        'id' => $prospect->id,
                        'bds_application_id' => $prospect->bds_application_id,
                        'sequence_number' => $prospect->sequence_number,
                        'company_name' => $prospect->application?->company_name,
                        'applicant_name' => $prospect->application?->full_name,
                        'assessment_status' => $prospect->application?->assessment_status,
                        'adjudication_result' => $prospect->application?->adjudication_result,
                        'consolidated_total_score' => (int) $prospect->consolidated_total_score,
                        'submitted_assessments_count' => $actualSubmittedCount,
                        'required_panel_submissions' => $panelistCount,
                        'missing_panel_submissions' => max($panelistCount - $actualSubmittedCount, 0),
                        'missing_panelists' => $missingPanelists
                            ->map(fn ($panelist) => [
                                'user_id' => $panelist->user_id,
                                'name' => $panelist->user?->name,
                                'panel_role' => $panelist->panel_role,
                            ])
                            ->values(),
                        'manager_decision' => $prospect->manager_decision,
                        'manager_notes' => $prospect->manager_notes,
                        'manager_decided_at' => $prospect->manager_decided_at?->toDateTimeString(),
                        'has_current_user_submitted' => $submittedAssessments
                            ->contains(fn ($assessment) => (int) $assessment->judge_id === $currentUserId),
                        'submitted_panelists' => $submittedAssessments
                            ->map(fn ($assessment) => [
                                'assessment_id' => $assessment->id,
                                'judge_id' => $assessment->judge_id,
                                'judge_name' => $assessment->judge?->name,
                                'total_score' => (int) $assessment->total_score,
                                'submitted_at' => $assessment->submitted_at?->toDateTimeString(),
                            ])
                            ->values(),
                        'workflow' => [
                            'can_consolidate' => $actualSubmittedCount >= $panelistCount,
                            'can_approve' => (int) $prospect->submitted_assessments_count >= $panelistCount,
                            'needs_more_panel_scores' => $actualSubmittedCount < $panelistCount,
                        ],
                    ];
                }),
        ];
    }
}
