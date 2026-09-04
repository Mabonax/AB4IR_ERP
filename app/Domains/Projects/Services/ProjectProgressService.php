<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectLocation;
use Illuminate\Support\Collection;

class ProjectProgressService
{
    public function summarizeProject(Project $project): array
    {
        $project->loadMissing([
            'program',
            'projectManager',
            'locations.facilitator',
            'locations.province',
            'locations.enrollments.beneficiary',
            'locations.milestoneAssessments',
            'locations.attendanceRegisters.entries',
            'milestones',
        ]);

        $activeMilestones = $project->milestones->where('is_active', true)->values();
        $requiredMilestones = $activeMilestones->where('is_required', true)->values();
        $totalMilestones = $activeMilestones->count();
        $requiredMilestoneCount = $requiredMilestones->count();
        $activeMilestoneIds = $activeMilestones->pluck('id')->all();
        $requiredMilestoneIds = $requiredMilestones->pluck('id')->all();
        $locationSummaries = $project->locations
            ->map(fn (ProjectLocation $location) => $this->summarizeLocation($location, $totalMilestones, $requiredMilestoneCount, $activeMilestoneIds, $requiredMilestoneIds))
            ->values();

        $overallExpectedAssessments = (int) $locationSummaries->sum('expected_assessments');
        $overallExpectedRequiredAssessments = (int) $locationSummaries->sum('expected_required_assessments');
        $overallAssessedAssessments = (int) $locationSummaries->sum('assessed_assessments');
        $overallCompletedAssessments = (int) $locationSummaries->sum('completed_assessments');
        $overallCompletedRequiredAssessments = (int) $locationSummaries->sum('completed_required_assessments');
        $overallFailedAssessments = (int) $locationSummaries->sum('failed_assessments');
        $overallActiveBeneficiaries = (int) $locationSummaries->sum('active_beneficiaries');
        $overallCompletedBeneficiaries = (int) $locationSummaries->sum('completed_beneficiaries');
        $overallAttendanceEntries = (int) $locationSummaries->sum('attendance_entries');
        $overallAttendedEntries = (int) $locationSummaries->sum('attended_entries');

        $blockers = [];

        if ($project->locations->isEmpty()) {
            $blockers[] = 'No delivery locations have been added to this project yet.';
        }

        if ($totalMilestones === 0) {
            $blockers[] = 'No project milestones are attached yet.';
        }

        if ($overallActiveBeneficiaries === 0) {
            $blockers[] = 'No active beneficiaries are enrolled across project locations.';
        }

        if ($overallAttendanceEntries === 0) {
            $blockers[] = 'No attendance has been captured across project locations yet.';
        }

        return [
            'summary' => [
                'project_manager_name' => $project->projectManager
                    ? trim($project->projectManager->first_name.' '.$project->projectManager->last_name)
                    : null,
                'total_locations' => $project->locations->count(),
                'total_milestones' => $totalMilestones,
                'required_milestones' => $requiredMilestoneCount,
                'total_beneficiaries' => (int) $locationSummaries->sum('total_beneficiaries'),
                'active_beneficiaries' => $overallActiveBeneficiaries,
                'completed_beneficiaries' => $overallCompletedBeneficiaries,
                'dropped_beneficiaries' => (int) $locationSummaries->sum('dropped_beneficiaries'),
                'expected_assessments' => $overallExpectedAssessments,
                'expected_required_assessments' => $overallExpectedRequiredAssessments,
                'assessed_assessments' => $overallAssessedAssessments,
                'completed_assessments' => $overallCompletedAssessments,
                'failed_assessments' => $overallFailedAssessments,
                'unassessed_assessments' => max($overallExpectedAssessments - $overallAssessedAssessments, 0),
                'registers_captured' => (int) $locationSummaries->sum('registers_captured'),
                'attendance_rate' => $this->percentage($overallAttendedEntries, $overallAttendanceEntries),
                'assessment_coverage_rate' => $this->percentage($overallAssessedAssessments, $overallExpectedAssessments),
                'milestone_completion_rate' => $this->percentage($overallCompletedRequiredAssessments, $overallExpectedRequiredAssessments),
                'pass_rate' => $this->percentage($overallCompletedAssessments, $overallAssessedAssessments),
                'failed_rate' => $this->percentage($overallFailedAssessments, $overallAssessedAssessments),
                'not_assessed_rate' => $this->percentage(max($overallExpectedAssessments - $overallAssessedAssessments, 0), $overallExpectedAssessments),
                'beneficiary_completion_rate' => $this->percentage($overallCompletedBeneficiaries, $overallActiveBeneficiaries),
                'blocked_locations' => $locationSummaries->where('is_blocked', true)->count(),
                'locations_complete' => $locationSummaries->where('delivery_status', 'Completed')->count(),
                'blockers' => $blockers,
            ],
            'locations' => $locationSummaries->all(),
        ];
    }

    public function summarizePortfolio(Collection $projects): array
    {
        $projectSummaries = $projects
            ->map(function (Project $project) {
                $progress = $this->summarizeProject($project);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'status_label' => ucfirst(str_replace('_', ' ', (string) $project->status)),
                    'program_title' => $project->program?->title,
                    'project_manager_name' => $progress['summary']['project_manager_name'],
                    'total_locations' => $progress['summary']['total_locations'],
                    'active_beneficiaries' => $progress['summary']['active_beneficiaries'],
                    'total_milestones' => $progress['summary']['total_milestones'],
                    'milestone_completion_rate' => $progress['summary']['milestone_completion_rate'],
                    'beneficiary_completion_rate' => $progress['summary']['beneficiary_completion_rate'],
                    'attendance_rate' => $progress['summary']['attendance_rate'],
                    'blocked_locations' => $progress['summary']['blocked_locations'],
                ];
            })
            ->values();

        return [
            'projects' => $projectSummaries->all(),
            'stats' => [
                'tracked_projects' => $projectSummaries->count(),
                'average_milestone_completion_rate' => round((float) $projectSummaries->avg('milestone_completion_rate'), 2),
                'average_beneficiary_completion_rate' => round((float) $projectSummaries->avg('beneficiary_completion_rate'), 2),
                'average_attendance_rate' => round((float) $projectSummaries->avg('attendance_rate'), 2),
                'blocked_locations' => (int) $projectSummaries->sum('blocked_locations'),
            ],
        ];
    }

    protected function summarizeLocation(ProjectLocation $location, int $totalMilestones, int $requiredMilestones, array $activeMilestoneIds, array $requiredMilestoneIds): array
    {
        $totalBeneficiaries = $location->enrollments->count();

        $activeEnrollments = $location->enrollments->filter(function (ProjectEnrollment $enrollment) {
            return in_array($enrollment->status, ['enrolled', 'completed'], true)
                && $enrollment->beneficiary?->attendance_status === 'active'
                && $enrollment->beneficiary?->isLifecycleActive();
        })->values();

        $droppedBeneficiaries = $location->enrollments->filter(function (ProjectEnrollment $enrollment) {
            return $enrollment->status === 'dropped'
                || $enrollment->beneficiary?->attendance_status === 'dropout'
                || ! ($enrollment->beneficiary?->isLifecycleActive() ?? true);
        })->count();

        $completedBeneficiaries = $activeEnrollments->filter(function (ProjectEnrollment $enrollment) use ($location, $requiredMilestones, $requiredMilestoneIds) {
            if ($requiredMilestones === 0) {
                return false;
            }

            $completedAssessments = $location->milestoneAssessments
                ->where('beneficiary_id', $enrollment->beneficiary_id)
                ->where('status', 'completed')
                ->whereIn('project_milestone_id', $requiredMilestoneIds)
                ->pluck('project_milestone_id')
                ->unique()
                ->count();

            return $completedAssessments >= $requiredMilestones;
        })->count();

        $expectedAssessments = $activeEnrollments->count() * $totalMilestones;
        $expectedRequiredAssessments = $activeEnrollments->count() * $requiredMilestones;
        $activeBeneficiaryIds = $activeEnrollments->pluck('beneficiary_id')->all();
        $scopedAssessments = $location->milestoneAssessments
            ->whereIn('project_milestone_id', $activeMilestoneIds)
            ->whereIn('beneficiary_id', $activeBeneficiaryIds);
        $completedAssessments = $scopedAssessments->where('status', 'completed')->count();
        $failedAssessments = $scopedAssessments->where('status', 'failed')->count();
        $assessedAssessments = $completedAssessments + $failedAssessments;
        $completedRequiredAssessments = $scopedAssessments
            ->whereIn('project_milestone_id', $requiredMilestoneIds)
            ->where('status', 'completed')
            ->count();
        $unassessedAssessments = max($expectedAssessments - $assessedAssessments, 0);
        $attendanceEntries = 0;
        $attendedEntries = 0;

        foreach ($location->attendanceRegisters as $register) {
            if ($register->is_holiday) {
                continue;
            }

            $attendanceEntries += $register->entries->count();
            $attendedEntries += $register->entries
                ->whereIn('status', ['present', 'excused'])
                ->count();
        }

        $attendanceRate = $this->percentage($attendedEntries, $attendanceEntries);
        $milestoneCompletionRate = $this->percentage($completedRequiredAssessments, $expectedRequiredAssessments);
        $assessmentCoverageRate = $this->percentage($assessedAssessments, $expectedAssessments);
        $passRate = $this->percentage($completedAssessments, $assessedAssessments);
        $beneficiaryCompletionRate = $this->percentage($completedBeneficiaries, $activeEnrollments->count());

        $blockers = [];

        if (! $location->facilitator_id) {
            $blockers[] = 'No facilitator is assigned to this location.';
        }

        if ($activeEnrollments->isEmpty()) {
            $blockers[] = 'No active beneficiaries are enrolled at this location.';
        }

        if ($attendanceEntries === 0) {
            $blockers[] = 'Attendance has not been captured for this location.';
        }

        if ($expectedRequiredAssessments > 0 && $completedRequiredAssessments < $expectedRequiredAssessments) {
            $blockers[] = 'Milestone delivery is still incomplete at this location.';
        }

        $deliveryStatus = $this->deliveryStatus($activeEnrollments->count(), $totalMilestones, $expectedAssessments, $assessedAssessments, $completedRequiredAssessments, $expectedRequiredAssessments, $attendanceEntries);

        return [
            'id' => $location->id,
            'location' => $location->province?->name,
            'facilitator_name' => $location->facilitator
                ? trim($location->facilitator->name.' '.$location->facilitator->surname)
                : null,
            'training_venue_address' => $location->training_venue_address,
            'total_beneficiaries' => $totalBeneficiaries,
            'active_beneficiaries' => $activeEnrollments->count(),
            'completed_beneficiaries' => $completedBeneficiaries,
            'dropped_beneficiaries' => $droppedBeneficiaries,
            'total_milestones' => $totalMilestones,
            'required_milestones' => $requiredMilestones,
            'expected_assessments' => $expectedAssessments,
            'expected_required_assessments' => $expectedRequiredAssessments,
            'assessed_assessments' => $assessedAssessments,
            'completed_assessments' => $completedAssessments,
            'completed_required_assessments' => $completedRequiredAssessments,
            'failed_assessments' => $failedAssessments,
            'unassessed_assessments' => $unassessedAssessments,
            'registers_captured' => $location->attendanceRegisters->where('is_holiday', false)->count(),
            'attendance_entries' => $attendanceEntries,
            'attended_entries' => $attendedEntries,
            'attendance_rate' => $attendanceRate,
            'assessment_coverage_rate' => $assessmentCoverageRate,
            'milestone_completion_rate' => $milestoneCompletionRate,
            'pass_rate' => $passRate,
            'failed_rate' => $this->percentage($failedAssessments, $assessedAssessments),
            'not_assessed_rate' => $this->percentage($unassessedAssessments, $expectedAssessments),
            'beneficiary_completion_rate' => $beneficiaryCompletionRate,
            'delivery_status' => $deliveryStatus,
            'is_blocked' => $blockers !== [],
            'blockers' => $blockers,
        ];
    }

    protected function deliveryStatus(int $beneficiaries, int $milestones, int $expected, int $assessed, int $completed, int $expectedRequired, int $attendanceEntries): string
    {
        if ($beneficiaries === 0 || $milestones === 0) {
            return 'Blocked';
        }

        if ($assessed === 0 && $attendanceEntries === 0) {
            return 'Not Started';
        }

        if ($expectedRequired > 0 && $completed >= $expectedRequired) {
            return 'Completed';
        }

        if ($expected > 0 && $assessed < $expected && $attendanceEntries === 0) {
            return 'At Risk';
        }

        return 'In Progress';
    }

    protected function percentage(int $completed, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($completed / $total) * 100, 2);
    }
}
