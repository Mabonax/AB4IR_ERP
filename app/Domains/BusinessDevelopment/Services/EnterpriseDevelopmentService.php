<?php

namespace App\Domains\BusinessDevelopment\Services;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentCriterion;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentGap;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentHistory;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentNeed;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentPlan;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentPlanItem;
use App\Domains\BusinessDevelopment\Models\EnterpriseDiagnostic;
use App\Domains\BusinessDevelopment\Models\EnterpriseDiagnosticCriterion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnterpriseDevelopmentService
{
    public const MATURITY_SCORES = [
        'not_assessed' => null,
        'not_started' => 0,
        'emerging' => 25,
        'developing' => 50,
        'established' => 75,
        'verified' => 100,
        'not_applicable' => null,
    ];

    public function workspace(BdsIncubatee $incubatee): array
    {
        $diagnostics = EnterpriseDiagnostic::query()
            ->where('bds_incubatee_id', $incubatee->id)
            ->with(['criteria.evidenceFile:id,title,original_name', 'gaps'])
            ->latest('assessment_date')
            ->get();

        $baseline = $diagnostics->firstWhere('assessment_type', 'baseline');
        $current = $diagnostics->firstWhere('status', 'completed') ?? $baseline;

        return [
            'overview' => [
                'baseline_score' => $baseline?->overall_score !== null ? (float) $baseline->overall_score : null,
                'current_score' => $current?->overall_score !== null ? (float) $current->overall_score : null,
                'change_points' => $baseline && $current && $baseline->id !== $current->id
                    ? round((float) $current->overall_score - (float) $baseline->overall_score, 2)
                    : null,
                'dimension_scores' => $current?->dimension_scores ?? [],
            ],
            'diagnostics' => $diagnostics,
            'open_gaps' => EnterpriseDevelopmentGap::query()
                ->where('bds_incubatee_id', $incubatee->id)
                ->where('status', 'open')
                ->latest()
                ->get(),
            'needs' => EnterpriseDevelopmentNeed::query()
                ->where('bds_incubatee_id', $incubatee->id)
                ->latest()
                ->get(),
            'plans' => EnterpriseDevelopmentPlan::query()
                ->where('bds_incubatee_id', $incubatee->id)
                ->with(['items.need', 'items.responsibleUser:id,name'])
                ->latest()
                ->get(),
            'history' => EnterpriseDevelopmentHistory::query()
                ->where('bds_incubatee_id', $incubatee->id)
                ->with('actor:id,name')
                ->latest('occurred_at')
                ->take(100)
                ->get(),
        ];
    }

    public function createDiagnostic(BdsIncubatee $incubatee, array $data, User $actor): EnterpriseDiagnostic
    {
        $this->assertCan($actor, 'enterprise-development.diagnostics.create');

        return DB::transaction(function () use ($incubatee, $data, $actor): EnterpriseDiagnostic {
            $type = $data['assessment_type'];

            if ($type === 'baseline' && EnterpriseDiagnostic::query()
                ->where('bds_incubatee_id', $incubatee->id)
                ->where('assessment_type', 'baseline')
                ->exists()) {
                throw ValidationException::withMessages([
                    'assessment_type' => ['A baseline diagnostic already exists for this incubatee. Create a periodic reassessment instead.'],
                ]);
            }

            $diagnostic = EnterpriseDiagnostic::query()->create([
                'bds_incubatee_id' => $incubatee->id,
                'assessment_type' => $type,
                'assessment_date' => $data['assessment_date'],
                'assessor_id' => $actor->id,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'outcome_baseline' => $this->outcomeBaselineFrom($data),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $criteria = EnterpriseDevelopmentCriterion::query()
                ->with('dimension')
                ->where('active', true)
                ->whereHas('dimension', fn ($query) => $query->where('active', true))
                ->orderBy('sequence')
                ->get();

            foreach ($criteria as $criterion) {
                EnterpriseDiagnosticCriterion::query()->create([
                    'enterprise_diagnostic_id' => $diagnostic->id,
                    'criterion_id' => $criterion->id,
                    'dimension_id' => $criterion->dimension_id,
                    'criterion_code' => $criterion->code,
                    'criterion_name' => $criterion->name,
                    'dimension_code' => $criterion->dimension?->code ?? 'unknown',
                    'dimension_name' => $criterion->dimension?->name ?? 'Unknown',
                    'criterion_weighting' => $criterion->weighting,
                    'dimension_weighting' => $criterion->dimension?->weighting ?? 1,
                    'evidence_required' => $criterion->evidence_required,
                    'required' => $criterion->required,
                    'maturity_status' => 'not_assessed',
                    'maturity_score' => null,
                ]);
            }

            $this->history($incubatee, 'diagnostic_created', ucfirst($type).' diagnostic started', null, $actor);

            return $diagnostic->load('criteria');
        });
    }

    public function saveCriteria(EnterpriseDiagnostic $diagnostic, array $criteria, User $actor): EnterpriseDiagnostic
    {
        $this->assertCan($actor, 'enterprise-development.diagnostics.edit');
        $this->assertDiagnosticEditable($diagnostic);

        return DB::transaction(function () use ($diagnostic, $criteria, $actor): EnterpriseDiagnostic {
            $existing = $diagnostic->criteria()->get()->keyBy('id');

            foreach ($criteria as $row) {
                $criterion = $existing->get((int) $row['id']);
                if (! $criterion) {
                    continue;
                }

                $status = $row['maturity_status'];
                $criterion->update([
                    'maturity_status' => $status,
                    'maturity_score' => self::MATURITY_SCORES[$status],
                    'assessor_observation' => $row['assessor_observation'] ?? null,
                    'evidence_document_file_id' => $row['evidence_document_file_id'] ?? null,
                    'evidence_label' => $row['evidence_label'] ?? null,
                    'verified_at' => $status === 'verified' ? ($row['verified_at'] ?? now()->toDateString()) : ($row['verified_at'] ?? null),
                    'verified_by' => $status === 'verified' ? ($row['verified_by'] ?? $actor->id) : ($row['verified_by'] ?? null),
                    'expires_at' => $row['expires_at'] ?? null,
                ]);
            }

            $diagnostic->update([
                'status' => 'in_progress',
                'updated_by' => $actor->id,
            ]);

            $this->recalculate($diagnostic->refresh());

            return $diagnostic->refresh()->load(['criteria.evidenceFile', 'gaps']);
        });
    }

    public function completeDiagnostic(EnterpriseDiagnostic $diagnostic, User $actor): EnterpriseDiagnostic
    {
        $this->assertCan($actor, 'enterprise-development.diagnostics.complete');
        $this->assertDiagnosticEditable($diagnostic);

        return DB::transaction(function () use ($diagnostic, $actor): EnterpriseDiagnostic {
            $unassessedRequired = $diagnostic->criteria()
                ->where('required', true)
                ->where('maturity_status', 'not_assessed')
                ->count();

            if ($unassessedRequired > 0) {
                throw ValidationException::withMessages([
                    'criteria' => ["{$unassessedRequired} required criteria still need assessment."],
                ]);
            }

            $this->recalculate($diagnostic->refresh());
            $diagnostic->refresh()->update([
                'status' => 'completed',
                'completed_at' => now(),
                'locked_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->generateGaps($diagnostic->refresh());
            $this->history($diagnostic->incubatee, 'diagnostic_completed', ucfirst($diagnostic->assessment_type).' diagnostic completed', null, $actor);

            return $diagnostic->refresh()->load(['criteria.evidenceFile', 'gaps']);
        });
    }

    public function createNeedFromGap(EnterpriseDevelopmentGap $gap, array $data, User $actor): EnterpriseDevelopmentNeed
    {
        $this->assertCan($actor, 'enterprise-development.needs.manage');

        return DB::transaction(function () use ($gap, $data, $actor): EnterpriseDevelopmentNeed {
            $need = EnterpriseDevelopmentNeed::query()->create([
                'bds_incubatee_id' => $gap->bds_incubatee_id,
                'enterprise_diagnostic_id' => $gap->enterprise_diagnostic_id,
                'development_gap_id' => $gap->id,
                'title' => $data['title'] ?? $gap->criterion_name,
                'dimension_code' => $gap->dimension_code,
                'dimension_name' => $gap->dimension_name,
                'priority' => $data['priority'] ?? $gap->severity,
                'reason' => $data['reason'] ?? $gap->reason,
                'source' => 'diagnostic',
                'status' => 'open',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $gap->update(['status' => 'converted']);
            $this->history($need->incubatee, 'development_need_created', 'Development need created', $need->title, $actor);

            return $need;
        });
    }

    public function createPlan(BdsIncubatee $incubatee, array $data, User $actor): EnterpriseDevelopmentPlan
    {
        $this->assertCan($actor, 'enterprise-development.plans.manage');

        return DB::transaction(function () use ($incubatee, $data, $actor): EnterpriseDevelopmentPlan {
            $plan = EnterpriseDevelopmentPlan::query()->create([
                'bds_incubatee_id' => $incubatee->id,
                'baseline_diagnostic_id' => $data['baseline_diagnostic_id'] ?? null,
                'title' => $data['title'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $plan->items()->create([
                    'development_need_id' => $item['development_need_id'] ?? null,
                    'objective' => $item['objective'],
                    'priority' => $item['priority'] ?? 'medium',
                    'target_date' => $item['target_date'] ?? null,
                    'responsible_user_id' => $item['responsible_user_id'] ?? null,
                    'status' => $item['status'] ?? 'open',
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $this->history($incubatee, 'development_plan_created', 'Development plan created', $plan->title, $actor);

            return $plan->load('items');
        });
    }

    public function updateNeed(EnterpriseDevelopmentNeed $need, array $data, User $actor): EnterpriseDevelopmentNeed
    {
        $this->assertCan($actor, 'enterprise-development.needs.manage');

        $need->update([
            'title' => $data['title'] ?? $need->title,
            'priority' => $data['priority'] ?? $need->priority,
            'reason' => array_key_exists('reason', $data) ? $data['reason'] : $need->reason,
            'status' => $data['status'] ?? $need->status,
            'updated_by' => $actor->id,
        ]);

        $this->history($need->incubatee, 'development_need_updated', 'Development need updated', $need->fresh()->title, $actor);

        return $need->fresh();
    }

    public function updatePlanItem(EnterpriseDevelopmentPlanItem $item, array $data, User $actor): EnterpriseDevelopmentPlanItem
    {
        $this->assertCan($actor, 'enterprise-development.plans.manage');

        $item->update([
            'objective' => $data['objective'] ?? $item->objective,
            'priority' => $data['priority'] ?? $item->priority,
            'target_date' => array_key_exists('target_date', $data) ? $data['target_date'] : $item->target_date,
            'responsible_user_id' => array_key_exists('responsible_user_id', $data) ? $data['responsible_user_id'] : $item->responsible_user_id,
            'status' => $data['status'] ?? $item->status,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $item->notes,
        ]);

        $item->load('plan.incubatee');
        $this->history($item->plan->incubatee, 'development_plan_item_updated', 'Development plan item updated', $item->fresh()->objective, $actor);

        return $item->fresh();
    }

    protected function recalculate(EnterpriseDiagnostic $diagnostic): void
    {
        $criteria = $diagnostic->criteria()->get();
        $dimensionScores = [];

        foreach ($criteria->groupBy('dimension_code') as $dimensionCode => $rows) {
            $scoredRows = $rows->filter(fn ($row) => $row->maturity_score !== null && $row->maturity_status !== 'not_applicable');
            $weightSum = (float) $scoredRows->sum(fn ($row) => (float) $row->criterion_weighting);
            $score = $weightSum > 0
                ? round($scoredRows->sum(fn ($row) => (float) $row->maturity_score * (float) $row->criterion_weighting) / $weightSum, 2)
                : null;

            $first = $rows->first();
            $dimensionScores[] = [
                'code' => $dimensionCode,
                'name' => $first?->dimension_name,
                'score' => $score,
                'assessed' => $scoredRows->count(),
                'total' => $rows->filter(fn ($row) => $row->maturity_status !== 'not_applicable')->count(),
                'weighting' => (float) ($first?->dimension_weighting ?? 1),
            ];
        }

        $overallRows = collect($dimensionScores)->filter(fn ($row) => $row['score'] !== null);
        $overallWeight = (float) $overallRows->sum('weighting');
        $overall = $overallWeight > 0
            ? round($overallRows->sum(fn ($row) => (float) $row['score'] * (float) $row['weighting']) / $overallWeight, 2)
            : null;

        $diagnostic->update([
            'overall_score' => $overall,
            'dimension_scores' => $dimensionScores,
        ]);
    }

    protected function generateGaps(EnterpriseDiagnostic $diagnostic): void
    {
        $diagnostic->gaps()->delete();

        $gapStatuses = ['not_started', 'emerging', 'developing'];
        $criteria = $diagnostic->criteria()
            ->whereIn('maturity_status', $gapStatuses)
            ->get();

        foreach ($criteria as $criterion) {
            EnterpriseDevelopmentGap::query()->create([
                'enterprise_diagnostic_id' => $diagnostic->id,
                'bds_incubatee_id' => $diagnostic->bds_incubatee_id,
                'criterion_result_id' => $criterion->id,
                'dimension_code' => $criterion->dimension_code,
                'dimension_name' => $criterion->dimension_name,
                'criterion_code' => $criterion->criterion_code,
                'criterion_name' => $criterion->criterion_name,
                'severity' => $criterion->maturity_status === 'not_started' ? 'high' : ($criterion->maturity_status === 'emerging' ? 'medium' : 'low'),
                'reason' => $criterion->assessor_observation ?: 'Criterion remains below established maturity.',
                'status' => 'open',
            ]);
        }
    }

    protected function outcomeBaselineFrom(array $data): array
    {
        return array_filter([
            'employees' => $data['baseline_employees'] ?? null,
            'turnover' => $data['baseline_turnover'] ?? null,
            'markets_accessed' => $data['baseline_markets_accessed'] ?? null,
            'funding_accessed' => $data['baseline_funding_accessed'] ?? null,
            'customers' => $data['baseline_customers'] ?? null,
            'observation_date' => $data['assessment_date'] ?? now()->toDateString(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function assertDiagnosticEditable(EnterpriseDiagnostic $diagnostic): void
    {
        if (in_array($diagnostic->status, ['completed', 'locked'], true) || $diagnostic->locked_at !== null) {
            throw ValidationException::withMessages([
                'status' => ['Completed diagnostics are locked. Create a reassessment instead.'],
            ]);
        }
    }

    protected function assertCan(User $actor, string $permission): void
    {
        if (! $actor->can($permission) && ! $actor->can('domain.business-development.manage')) {
            throw ValidationException::withMessages([
                'authorization' => ['You are not authorized to manage enterprise development.'],
            ]);
        }
    }

    protected function history(BdsIncubatee $incubatee, string $type, string $title, ?string $details, User $actor): void
    {
        EnterpriseDevelopmentHistory::query()->create([
            'bds_incubatee_id' => $incubatee->id,
            'event_type' => $type,
            'title' => $title,
            'details' => $details,
            'actor_id' => $actor->id,
            'occurred_at' => now(),
        ]);
    }
}
