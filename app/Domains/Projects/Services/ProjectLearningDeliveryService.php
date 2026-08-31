<?php

namespace App\Domains\Projects\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectLearningMapping;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectLearningDeliveryService
{
    public function __construct(
        private readonly LmsLearningDeliveryClient $lms,
        private readonly ProjectHistoryService $history,
    ) {
    }

    public function mapOffering(Project $project, int $lmsOfferingId, User $actor): array
    {
        $response = $this->lms->mapProject($project, $lmsOfferingId, $actor);

        if (($response['status'] ?? null) === 'rejected') {
            return $response;
        }

        $mappingPayload = $response['mapping'] ?? null;
        $offering = $mappingPayload['offering'] ?? null;

        $mapping = ProjectLearningMapping::query()->updateOrCreate(
            ['project_id' => $project->id, 'lms_offering_id' => (string) $lmsOfferingId],
            [
                'status' => 'active',
                'offering_snapshot' => $offering,
                'mapped_at' => now(),
                'mapped_by_user_id' => $actor->id,
            ]
        );

        $this->history->record(
            $project,
            'lms_mapping_updated',
            sprintf('Mapped project to LMS offering %s.', $offering['name'] ?? "#{$lmsOfferingId}"),
            $actor,
            ['lms_offering_id' => $lmsOfferingId, 'result' => $response['status'] ?? 'mapped']
        );

        return [
            'status' => $mapping->wasRecentlyCreated ? 'mapped' : 'already_mapped',
            'mapping_id' => $mapping->id,
            'offering' => $offering,
        ];
    }

    public function learnerProvisioningWorkspace(Project $project): array
    {
        $mapping = $this->activeMapping($project);
        $beneficiaries = Beneficiary::query()
            ->with('projectEnrollments')
            ->whereHas('projectEnrollments', fn ($query) => $query->where('project_id', $project->id))
            ->orderBy('name')
            ->get();

        $items = $beneficiaries->map(function (Beneficiary $beneficiary) use ($project, $mapping) {
            $eligibility = $this->beneficiaryEligibility($project, $beneficiary);
            $summary = $this->lms->beneficiarySummary($beneficiary);

            return [
                'erp_beneficiary_id' => $beneficiary->id,
                'name' => $beneficiary->full_name,
                'email' => $beneficiary->email,
                'project_status' => $beneficiary->projectEnrollments->firstWhere('project_id', $project->id)?->status,
                'eligible' => $eligibility['eligible'],
                'reason' => $mapping ? $eligibility['reason'] : 'Project has no LMS learning delivery mapping.',
                'lms_status' => $summary['access_state'] ?? $summary['lms_access'] ?? 'unavailable',
                'invitation_status' => $summary['invitation_status'] ?? null,
            ];
        })->values();

        return [
            'has_mapping' => (bool) $mapping,
            'mapping' => $mapping?->only(['id', 'lms_offering_id', 'offering_snapshot', 'status']),
            'metrics' => $this->accessMetrics($items, $beneficiaries->count()),
            'items' => $items,
        ];
    }

    public function facilitatorProvisioningWorkspace(Project $project): array
    {
        $mapping = $this->activeMapping($project);
        $facilitators = Facilitator::query()
            ->whereHas('projectLocations', fn ($query) => $query->where('project_id', $project->id))
            ->orderBy('name')
            ->get();

        $items = $facilitators->map(function (Facilitator $facilitator) use ($project, $mapping) {
            $eligibility = $this->facilitatorEligibility($project, $facilitator);
            $summary = $this->lms->facilitatorSummary($facilitator);

            return [
                'erp_facilitator_id' => $facilitator->id,
                'name' => trim("{$facilitator->name} {$facilitator->surname}"),
                'email' => $facilitator->email,
                'eligible' => $eligibility['eligible'],
                'reason' => $mapping ? $eligibility['reason'] : 'Project has no LMS learning delivery mapping.',
                'lms_status' => $summary['access_state'] ?? $summary['lms_access'] ?? 'unavailable',
                'invitation_status' => $summary['invitation_status'] ?? null,
            ];
        })->values();

        return [
            'has_mapping' => (bool) $mapping,
            'mapping' => $mapping?->only(['id', 'lms_offering_id', 'offering_snapshot', 'status']),
            'metrics' => $this->accessMetrics($items, $facilitators->count()),
            'items' => $items,
        ];
    }

    public function provisionLearners(Project $project, array $beneficiaryIds, User $actor): array
    {
        $mapping = $this->activeMapping($project);
        if (! $mapping) {
            return ['status' => 'rejected', 'reason' => 'Project has no LMS learning delivery mapping.', 'items' => []];
        }

        $items = collect($beneficiaryIds)->map(function ($beneficiaryId) use ($project, $mapping) {
            $beneficiary = Beneficiary::query()->with('projectEnrollments')->find($beneficiaryId);
            if (! $beneficiary) {
                return ['erp_beneficiary_id' => (string) $beneficiaryId, 'status' => 'rejected', 'reason' => 'Beneficiary not found.'];
            }

            $eligibility = $this->beneficiaryEligibility($project, $beneficiary);
            if (! $eligibility['eligible']) {
                return ['erp_beneficiary_id' => (string) $beneficiary->id, 'status' => 'rejected', 'reason' => $eligibility['reason']];
            }

            return [
                'erp_beneficiary_id' => (string) $beneficiary->id,
                ...$this->lms->provisionLearner($project, $beneficiary, $mapping),
            ];
        })->values();

        $this->history->record($project, 'lms_learner_provisioning_requested', 'Requested LMS learner provisioning.', $actor, [
            'items' => $items->all(),
        ]);

        return ['status' => 'processed', 'items' => $items->all(), 'summary' => $this->summarizeItems($items)];
    }

    public function provisionFacilitators(Project $project, array $facilitatorIds, User $actor): array
    {
        $mapping = $this->activeMapping($project);
        if (! $mapping) {
            return ['status' => 'rejected', 'reason' => 'Project has no LMS learning delivery mapping.', 'items' => []];
        }

        $items = collect($facilitatorIds)->map(function ($facilitatorId) use ($project, $mapping) {
            $facilitator = Facilitator::query()->find($facilitatorId);
            if (! $facilitator) {
                return ['erp_facilitator_id' => (string) $facilitatorId, 'status' => 'rejected', 'reason' => 'Facilitator not found.'];
            }

            $eligibility = $this->facilitatorEligibility($project, $facilitator);
            if (! $eligibility['eligible']) {
                return ['erp_facilitator_id' => (string) $facilitator->id, 'status' => 'rejected', 'reason' => $eligibility['reason']];
            }

            return [
                'erp_facilitator_id' => (string) $facilitator->id,
                ...$this->lms->provisionFacilitator($project, $facilitator, $mapping),
            ];
        })->values();

        $this->history->record($project, 'lms_facilitator_provisioning_requested', 'Requested LMS facilitator provisioning.', $actor, [
            'items' => $items->all(),
        ]);

        return ['status' => 'processed', 'items' => $items->all(), 'summary' => $this->summarizeItems($items)];
    }

    public function assignFacilitator(Project $project, Facilitator $facilitator, User $actor): array
    {
        $mapping = $this->activeMapping($project);
        if (! $mapping) {
            return ['status' => 'rejected', 'reason' => 'Project has no LMS learning delivery mapping.'];
        }

        $eligibility = $this->facilitatorEligibility($project, $facilitator);
        if (! $eligibility['eligible']) {
            return ['status' => 'rejected', 'reason' => $eligibility['reason']];
        }

        $result = $this->lms->assignFacilitator($project, $facilitator, $mapping);

        $this->history->record($project, 'lms_teaching_assignment_requested', 'Requested LMS teaching assignment.', $actor, [
            'erp_facilitator_id' => $facilitator->id,
            'lms_offering_id' => $mapping->lms_offering_id,
            'result' => $result,
        ]);

        return $result;
    }

    public function beneficiaryEligibility(Project $project, Beneficiary $beneficiary): array
    {
        if (! $beneficiary->projectEnrollments()->where('project_id', $project->id)->exists()) {
            return ['eligible' => false, 'reason' => 'Beneficiary is not assigned to this project.'];
        }

        if (! $beneficiary->isLifecycleActive()) {
            return ['eligible' => false, 'reason' => "Beneficiary status '{$beneficiary->status}' is not eligible for LMS delivery."];
        }

        if (blank($beneficiary->email)) {
            return ['eligible' => false, 'reason' => 'Beneficiary has no usable email/contact method.'];
        }

        return ['eligible' => true, 'reason' => 'Eligible for LMS provisioning.'];
    }

    public function facilitatorEligibility(Project $project, Facilitator $facilitator): array
    {
        if (! DB::table('project_locations')->where('project_id', $project->id)->where('facilitator_id', $facilitator->id)->exists()) {
            return ['eligible' => false, 'reason' => 'Facilitator is not currently assigned to this ERP project.'];
        }

        if (blank($facilitator->email)) {
            return ['eligible' => false, 'reason' => 'Facilitator has no usable email/contact method.'];
        }

        return ['eligible' => true, 'reason' => 'Eligible for LMS facilitator provisioning.'];
    }

    public function activeMapping(Project $project): ?ProjectLearningMapping
    {
        return $project->learningMappings()->where('status', 'active')->latest('mapped_at')->first();
    }

    private function summarizeItems(Collection $items): array
    {
        return $items->groupBy(fn (array $item) => $item['status'] ?? 'unknown')
            ->map(fn (Collection $group) => $group->count())
            ->all();
    }

    private function accessMetrics(Collection $items, int $total): array
    {
        $states = $items->groupBy(fn (array $item) => $item['lms_status'] ?? 'not_provisioned')
            ->map(fn (Collection $group) => $group->count());

        return [
            'total' => $total,
            'active' => (int) ($states['active'] ?? 0),
            'suspended' => (int) ($states['suspended'] ?? 0),
            'invitation_pending' => (int) ($states['invitation_pending'] ?? 0),
            'invitation_expired' => (int) ($states['invitation_expired'] ?? 0),
            'not_provisioned' => (int) ($states['not_provisioned'] ?? 0),
            'unavailable' => (int) ($states['unavailable'] ?? 0),
        ];
    }
}
