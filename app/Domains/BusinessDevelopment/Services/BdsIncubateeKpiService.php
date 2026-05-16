<?php

namespace App\Domains\BusinessDevelopment\Services;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\BdsIncubateeKpi;
use App\Domains\BusinessDevelopment\Models\BdsIncubateeKpiReview;
use App\Domains\BusinessDevelopment\Models\BdsKpiDefinition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BdsIncubateeKpiService
{
    public function assignKpi(BdsIncubatee $incubatee, array $data, User $actor): BdsIncubateeKpi
    {
        $this->assertManager($actor);

        $definition = BdsKpiDefinition::query()->findOrFail((int) $data['bds_kpi_definition_id']);

        return DB::transaction(function () use ($incubatee, $definition, $data, $actor) {
            $existing = BdsIncubateeKpi::query()
                ->where('bds_incubatee_id', $incubatee->id)
                ->where('bds_kpi_definition_id', $definition->id)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'bds_kpi_definition_id' => ['This KPI is already assigned to the incubatee.'],
                ]);
            }

            return BdsIncubateeKpi::query()->create([
                'bds_incubatee_id' => $incubatee->id,
                'bds_kpi_definition_id' => $definition->id,
                'target_value' => $data['target_value'] ?? $definition->default_target_value,
                'baseline_value' => $data['baseline_value'] ?? null,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'status' => 'active',
                'assigned_by' => $actor->id,
            ]);
        });
    }

    public function recordReview(BdsIncubateeKpi $kpi, array $data, User $actor): BdsIncubateeKpiReview
    {
        $this->assertManager($actor);

        return DB::transaction(function () use ($kpi, $data, $actor) {
            $progress = (int) ($data['progress_percent'] ?? 0);

            if ($progress < 0 || $progress > 100) {
                throw ValidationException::withMessages([
                    'progress_percent' => ['Progress percent must be between 0 and 100.'],
                ]);
            }

            $review = BdsIncubateeKpiReview::query()->create([
                'bds_incubatee_kpi_id' => $kpi->id,
                'review_date' => $data['review_date'] ?? now()->toDateString(),
                'actual_value' => $data['actual_value'] ?? null,
                'progress_percent' => $progress,
                'status' => $data['status'] ?? $this->deriveStatus($progress),
                'evidence_notes' => $data['evidence_notes'] ?? null,
                'mentor_comments' => $data['mentor_comments'] ?? null,
                'reviewed_by' => $actor->id,
            ]);

            if ($progress >= 100) {
                $kpi->update([
                    'status' => 'completed',
                ]);
            }

            return $review;
        });
    }

    protected function deriveStatus(int $progress): string
    {
        return match (true) {
            $progress >= 100 => 'completed',
            $progress >= 70 => 'on_track',
            $progress >= 40 => 'needs_attention',
            default => 'at_risk',
        };
    }

    protected function assertManager(User $actor): void
    {
        if (! $actor->can('domain.business-development.manage')) {
            throw ValidationException::withMessages([
                'authorization' => ['You are not authorized to manage incubatee KPIs.'],
            ]);
        }
    }
}
