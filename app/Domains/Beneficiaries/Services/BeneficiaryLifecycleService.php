<?php

namespace App\Domains\Beneficiaries\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Beneficiaries\Models\BeneficiaryOutcome;
use App\Domains\Beneficiaries\Notifications\BeneficiaryLifecycleNotification;
use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Services\ProjectEnrollmentConsistencyService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BeneficiaryLifecycleService
{
    public function __construct(
        protected ProjectEnrollmentConsistencyService $enrollmentConsistency,
        protected BeneficiaryHistoryService $historyService,
    ) {}

    public function suspendBeneficiary(Beneficiary $beneficiary, User $actor, string $reason): Beneficiary
    {
        $this->assertNotArchived($beneficiary);

        if (($beneficiary->status ?? 'enrolled') === 'suspended') {
            throw ValidationException::withMessages([
                'reason' => ['This beneficiary is already suspended.'],
            ]);
        }

        return DB::transaction(function () use ($beneficiary, $actor, $reason) {
            $previousStatus = $beneficiary->status ?? 'enrolled';
            $beneficiary->update([
                'status' => 'suspended',
                'status_reason' => $reason,
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
            ]);

            $beneficiary->projectEnrollments()
                ->where('project_id', $beneficiary->project_id)
                ->where('status', 'enrolled')
                ->update(['status' => 'dropped']);

            $updated = $beneficiary->fresh($this->relations());

            $this->historyService->record(
                $updated,
                'suspended',
                sprintf('%s suspended beneficiary participation.', $actor->name),
                $actor,
                $previousStatus,
                'suspended',
                $reason,
            );

            $this->notifyManagers($updated, $actor, 'Beneficiary suspended', sprintf('%s suspended %s.', $actor->name, $updated->full_name));

            return $updated;
        });
    }

    public function reactivateBeneficiary(Beneficiary $beneficiary, User $actor, string $reason): Beneficiary
    {
        $this->assertNotArchived($beneficiary);

        if (($beneficiary->status ?? 'enrolled') === 'enrolled') {
            throw ValidationException::withMessages([
                'reason' => ['This beneficiary is already active.'],
            ]);
        }

        return DB::transaction(function () use ($beneficiary, $actor, $reason) {
            $previousStatus = $beneficiary->status ?? 'enrolled';

            $beneficiary->update([
                'status' => 'enrolled',
                'status_reason' => $reason,
                'reactivated_at' => now(),
                'reactivated_by' => $actor->id,
            ]);

            $currentEnrollment = $beneficiary->projectEnrollments()
                ->where('project_id', $beneficiary->project_id)
                ->latest('enrolled_at')
                ->first();

            if ($currentEnrollment) {
                $currentEnrollment->update(['status' => 'enrolled']);
            }

            $updated = $beneficiary->fresh($this->relations());

            $this->historyService->record(
                $updated,
                'reinstated',
                sprintf('%s reinstated beneficiary participation.', $actor->name),
                $actor,
                $previousStatus,
                'enrolled',
                $reason,
            );

            $this->notifyManagers($updated, $actor, 'Beneficiary reinstated', sprintf('%s reinstated %s.', $actor->name, $updated->full_name));

            return $updated;
        });
    }

    public function graduateBeneficiary(Beneficiary $beneficiary, User $actor, string $reason, ?string $outcomeType = null, ?string $outcomeNotes = null): Beneficiary
    {
        $this->assertNotArchived($beneficiary);

        return DB::transaction(function () use ($beneficiary, $actor, $reason, $outcomeType, $outcomeNotes) {
            $previousStatus = $beneficiary->status ?? 'enrolled';

            $beneficiary->update([
                'status' => 'graduated',
                'status_reason' => $reason,
                'graduated_at' => now(),
                'graduated_by' => $actor->id,
            ]);

            $beneficiary->projectEnrollments()
                ->where('project_id', $beneficiary->project_id)
                ->whereIn('status', ['enrolled', 'dropped'])
                ->update(['status' => 'completed']);

            $this->recordOutcome($beneficiary, $actor, $outcomeType, $outcomeNotes);
            $updated = $beneficiary->fresh($this->relations());

            $this->historyService->record(
                $updated,
                'graduated',
                sprintf('%s graduated the beneficiary.', $actor->name),
                $actor,
                $previousStatus,
                'graduated',
                $reason,
                ['outcome_type' => $outcomeType ?? 'unknown_outcome']
            );

            $this->notifyManagers($updated, $actor, 'Beneficiary graduated', sprintf('%s graduated %s.', $actor->name, $updated->full_name));

            return $updated;
        });
    }

    public function exitBeneficiary(Beneficiary $beneficiary, User $actor, string $reason, ?string $outcomeType = null, ?string $outcomeNotes = null): Beneficiary
    {
        $this->assertNotArchived($beneficiary);

        return DB::transaction(function () use ($beneficiary, $actor, $reason, $outcomeType, $outcomeNotes) {
            $previousStatus = $beneficiary->status ?? 'enrolled';

            $beneficiary->update([
                'status' => 'exited',
                'status_reason' => $reason,
                'exited_at' => now(),
                'exited_by' => $actor->id,
                'exit_reason' => $reason,
            ]);

            $beneficiary->projectEnrollments()
                ->where('project_id', $beneficiary->project_id)
                ->where('status', 'enrolled')
                ->update(['status' => 'dropped']);

            $this->recordOutcome($beneficiary, $actor, $outcomeType, $outcomeNotes);
            $updated = $beneficiary->fresh($this->relations());

            $this->historyService->record(
                $updated,
                'exited',
                sprintf('%s exited the beneficiary from the project.', $actor->name),
                $actor,
                $previousStatus,
                'exited',
                $reason,
                ['outcome_type' => $outcomeType ?? 'unknown_outcome']
            );

            $this->notifyManagers($updated, $actor, 'Beneficiary exited', sprintf('%s exited %s.', $actor->name, $updated->full_name));

            return $updated;
        });
    }

    public function transferBeneficiary(Beneficiary $beneficiary, User $actor, int $projectId, int $projectLocationId, string $reason): Beneficiary
    {
        $this->assertNotArchived($beneficiary);

        if ((int) $beneficiary->project_id === $projectId) {
            throw ValidationException::withMessages([
                'project_id' => ['Select a different project for transfer.'],
            ]);
        }

        return DB::transaction(function () use ($beneficiary, $actor, $projectId, $projectLocationId, $reason) {
            $previousStatus = $beneficiary->status ?? 'enrolled';
            $fromProject = $beneficiary->project;

            $beneficiary->update([
                'project_id' => $projectId,
                'status' => 'enrolled',
                'status_reason' => $reason,
                'reactivated_at' => now(),
                'reactivated_by' => $actor->id,
            ]);

            $this->enrollmentConsistency->syncBeneficiaryEnrollment(
                $beneficiary,
                $projectId,
                $projectLocationId,
                'enrolled',
                now(),
                currentProjectId: $fromProject?->id
            );

            $updated = $beneficiary->fresh($this->relations());

            $this->historyService->record(
                $updated,
                'transferred',
                sprintf('%s transferred the beneficiary to %s.', $actor->name, $updated->project?->name ?? 'the selected project'),
                $actor,
                $previousStatus,
                'enrolled',
                $reason,
                [
                    'from_project_id' => $fromProject?->id,
                    'from_project_name' => $fromProject?->name,
                    'to_project_id' => $updated->project?->id,
                    'to_project_name' => $updated->project?->name,
                    'to_project_location_id' => $projectLocationId,
                ]
            );

            $this->notifyManagers($updated, $actor, 'Beneficiary transferred', sprintf('%s transferred %s to %s.', $actor->name, $updated->full_name, $updated->project?->name ?? 'a new project'));

            return $updated;
        });
    }

    public function archiveBeneficiary(Beneficiary $beneficiary, User $actor, string $reason): void
    {
        $this->assertNotArchived($beneficiary);

        DB::transaction(function () use ($beneficiary, $actor, $reason) {
            $previousStatus = $beneficiary->status ?? 'enrolled';

            $beneficiary->update([
                'status' => 'archived',
                'status_reason' => $reason,
            ]);

            $beneficiary->delete();

            $archived = Beneficiary::withTrashed()->with($this->relations())->findOrFail($beneficiary->id);

            $this->historyService->record(
                $archived,
                'archived',
                sprintf('%s archived the beneficiary file.', $actor->name),
                $actor,
                $previousStatus,
                'archived',
                $reason,
            );

            $this->notifyManagers($archived, $actor, 'Beneficiary archived', sprintf('%s archived %s.', $actor->name, $archived->full_name));
        });
    }

    public function cohortMetrics(int $projectId): array
    {
        $base = Beneficiary::query()
            ->whereHas('projectEnrollments', fn ($query) => $query->where('project_id', $projectId));

        return [
            'graduated_beneficiaries' => (clone $base)->where('status', 'graduated')->count(),
            'exited_beneficiaries' => (clone $base)->where('status', 'exited')->count(),
            'employment_outcomes' => BeneficiaryOutcome::query()->where('project_id', $projectId)->where('outcome_type', 'employment')->count(),
            'further_education_outcomes' => BeneficiaryOutcome::query()->where('project_id', $projectId)->where('outcome_type', 'further_education')->count(),
            'unknown_outcomes' => BeneficiaryOutcome::query()->where('project_id', $projectId)->where('outcome_type', 'unknown_outcome')->count(),
        ];
    }

    protected function recordOutcome(Beneficiary $beneficiary, User $actor, ?string $outcomeType, ?string $notes): BeneficiaryOutcome
    {
        return BeneficiaryOutcome::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'program_id' => $beneficiary->project?->program_id,
            'project_id' => $beneficiary->project_id,
            'outcome_type' => $outcomeType ?? 'unknown_outcome',
            'notes' => $notes,
            'recorded_at' => now(),
            'recorded_by_user_id' => $actor->id,
        ]);
    }

    protected function notifyManagers(Beneficiary $beneficiary, User $actor, string $title, string $message): void
    {
        User::query()
            ->permission('domain.beneficiaries.manage')
            ->whereKeyNot($actor->id)
            ->get()
            ->each(fn (User $user) => $user->notify(new BeneficiaryLifecycleNotification($beneficiary, $title, $message)));
    }

    protected function assertNotArchived(Beneficiary $beneficiary): void
    {
        if ($beneficiary->trashed()) {
            throw ValidationException::withMessages([
                'beneficiary' => ['Archived beneficiaries cannot be modified.'],
            ]);
        }
    }

    protected function relations(): array
    {
        return [
            'project.program',
            'projectEnrollments.project.program',
            'projectEnrollments.location.province',
            'nextOfKin',
            'history.actor',
            'outcomes.project',
            'outcomes.program',
            'outcomes.recordedBy',
            'latestOutcome.recordedBy',
        ];
    }
}
