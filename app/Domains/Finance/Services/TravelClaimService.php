<?php

namespace App\Domains\Finance\Services;

use App\Domains\Finance\Models\TravelClaim;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TravelClaimService
{
    public const DEFAULT_TARIFF_PER_KM = 4.84;

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public function create(User $actor, array $data): TravelClaim
    {
        $actorStaff = $actor->staffMember;
        if (! $actorStaff) {
            throw ValidationException::withMessages([
                'claimant_staff_member_id' => 'A staff profile is required before travel claims can be submitted.',
            ]);
        }

        $claimant = StaffMember::query()->with(['department', 'manager.user', 'user'])->findOrFail($data['claimant_staff_member_id']);
        $this->assertClaimantScope($actor, $actorStaff, $claimant);
        $approver = $this->resolveApprover($claimant);

        if (! $approver) {
            throw ValidationException::withMessages([
                'claimant_staff_member_id' => 'This claimant does not have a valid approving authority. Assign a higher-level approver or CEO first.',
            ]);
        }

        $trips = collect($data['trips'] ?? [])->map(function (array $trip) {
            $actualDistance = round((float) $trip['actual_distance_km'], 2);
            $claimableDistance = round((float) $trip['claimable_distance_km'], 2);

            return [
                'travel_date' => $trip['travel_date'],
                'route_from' => trim((string) $trip['route_from']),
                'route_to' => trim((string) $trip['route_to']),
                'start_time' => filled($trip['start_time'] ?? null) ? (string) $trip['start_time'] : null,
                'end_time' => filled($trip['end_time'] ?? null) ? (string) $trip['end_time'] : null,
                'nature_of_duty' => filled($trip['nature_of_duty'] ?? null) ? trim((string) $trip['nature_of_duty']) : null,
                'actual_distance_km' => $actualDistance,
                'claimable_distance_km' => $claimableDistance,
            ];
        });

        if ($trips->isEmpty()) {
            throw ValidationException::withMessages([
                'trips' => 'At least one travel row is required.',
            ]);
        }

        $tariff = round((float) ($data['tariff_per_km'] ?? self::DEFAULT_TARIFF_PER_KM), 2);
        $totalActualDistance = round((float) $trips->sum('actual_distance_km'), 2);
        $totalClaimableDistance = round((float) $trips->sum('claimable_distance_km'), 2);
        $totalAmount = round($totalClaimableDistance * $tariff, 2);

        return DB::transaction(function () use ($actor, $actorStaff, $claimant, $data, $trips, $tariff, $totalActualDistance, $totalClaimableDistance, $totalAmount) {
            $claim = TravelClaim::query()->create([
                'claim_number' => $this->generateClaimNumber(),
                'claimant_staff_member_id' => $claimant->id,
                'department_id' => $claimant->department_id,
                'submitted_by_user_id' => $actor->id,
                'checked_by_staff_member_id' => $actorStaff->id,
                'claim_month' => Carbon::parse($data['claim_month'])->startOfMonth()->format('Y-m-d'),
                'claimant_name' => trim($claimant->first_name.' '.$claimant->last_name),
                'claimant_address' => $data['claimant_address'] ?? '',
                'vehicle_make_model' => filled($data['vehicle_make_model'] ?? null) ? trim((string) $data['vehicle_make_model']) : null,
                'vehicle_type' => filled($data['vehicle_type'] ?? null) ? trim((string) $data['vehicle_type']) : null,
                'vehicle_year' => filled($data['vehicle_year'] ?? null) ? (int) $data['vehicle_year'] : null,
                'engine_volume' => filled($data['engine_volume'] ?? null) ? trim((string) $data['engine_volume']) : null,
                'tariff_per_km' => $tariff,
                'home_distance_km' => round((float) ($data['home_distance_km'] ?? 0), 2),
                'status' => 'submitted',
                'approval_status' => self::APPROVAL_PENDING,
                'submitted_at' => now(),
                'total_actual_distance_km' => $totalActualDistance,
                'total_claimable_distance_km' => $totalClaimableDistance,
                'total_amount' => $totalAmount,
            ]);

            $claim->trips()->createMany($trips->map(fn (array $trip) => [
                ...$trip,
                'line_total' => round((float) $trip['claimable_distance_km'] * $tariff, 2),
            ])->all());

            return $claim->fresh(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'trips']);
        });
    }

    public function approve(TravelClaim $claim, User $actor, ?string $comment = null): TravelClaim
    {
        if ($claim->approval_status !== self::APPROVAL_PENDING) {
            throw ValidationException::withMessages([
                'approval_status' => 'This claim has already been decided.',
            ]);
        }

        if (! $this->canApprove($claim, $actor)) {
            throw new AuthorizationException('You are not the approving authority for this claim.');
        }

        $claim->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_by_user_id' => $actor->id,
            'approval_decided_at' => now(),
            'approval_comment' => $comment,
        ]);

        return $claim->fresh(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
    }

    public function rejectApproval(TravelClaim $claim, User $actor, ?string $comment = null): TravelClaim
    {
        if ($claim->approval_status !== self::APPROVAL_PENDING) {
            throw ValidationException::withMessages([
                'approval_status' => 'This claim has already been decided.',
            ]);
        }

        if (! $this->canApprove($claim, $actor)) {
            throw new AuthorizationException('You are not the approving authority for this claim.');
        }

        $claim->update([
            'approval_status' => self::APPROVAL_REJECTED,
            'approved_by_user_id' => $actor->id,
            'approval_decided_at' => now(),
            'approval_comment' => $comment,
            'status' => 'rejected',
        ]);

        return $claim->fresh(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
    }

    public function receive(TravelClaim $claim, User $actor, ?string $comment = null): TravelClaim
    {
        if ($claim->approval_status !== self::APPROVAL_APPROVED) {
            throw ValidationException::withMessages([
                'approval_status' => 'Finance can only receive claims after executive approval.',
            ]);
        }

        if (! in_array($claim->status, ['submitted', 'received'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted claims can be received by finance.',
            ]);
        }

        $claim->update([
            'status' => 'received',
            'finance_received_at' => $claim->finance_received_at ?? now(),
            'received_by_user_id' => $actor->id,
            'finance_comment' => $comment ?: $claim->finance_comment,
        ]);

        return $claim->fresh(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
    }

    public function pay(TravelClaim $claim, User $actor, ?string $comment = null): TravelClaim
    {
        if ($claim->approval_status !== self::APPROVAL_APPROVED) {
            throw ValidationException::withMessages([
                'approval_status' => 'Finance can only pay claims after executive approval.',
            ]);
        }

        if (! in_array($claim->status, ['submitted', 'received'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or received claims can be marked as paid.',
            ]);
        }

        $claim->update([
            'status' => 'paid',
            'finance_received_at' => $claim->finance_received_at ?? now(),
            'received_by_user_id' => $claim->received_by_user_id ?? $actor->id,
            'finance_paid_at' => now(),
            'paid_by_user_id' => $actor->id,
            'finance_comment' => $comment ?: $claim->finance_comment,
        ]);

        return $claim->fresh(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
    }

    public function reject(TravelClaim $claim, User $actor, ?string $comment = null): TravelClaim
    {
        $claim->update([
            'status' => 'rejected',
            'received_by_user_id' => $actor->id,
            'finance_comment' => $comment,
        ]);

        return $claim->fresh(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
    }

    public function visibleClaims(User $actor)
    {
        $query = TravelClaim::query()
            ->with(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);

        if ($actor->can('domain.finance.view') || $actor->can('domain.finance.manage')) {
            return $query->latest()->get();
        }

        $staff = $actor->staffMember;
        if (! $staff) {
            return collect();
        }

        return $query
            ->where(function ($builder) use ($actor, $staff) {
                $builder->where('submitted_by_user_id', $actor->id)
                    ->orWhere('claimant_staff_member_id', $staff->id)
                    ->orWhereHas('claimant', function ($claimantQuery) use ($staff) {
                        $claimantQuery->where('manager_id', $staff->id);
                    });
            })
            ->latest()
            ->get();
    }

    public function claimantOptions(User $actor): array
    {
        $staff = $actor->staffMember;
        if (! $staff) {
            return [];
        }

        return StaffMember::query()
            ->with('department')
            ->where('id', $staff->id)
            ->where(function ($query) {
                $query->where('is_manager', true)
                    ->orWhere('is_ceo', true);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (StaffMember $member) => [
                'id' => $member->id,
                'name' => trim($member->first_name.' '.$member->last_name),
                'department_name' => $member->department?->name,
            ])
            ->values()
            ->all();
    }

    public function mapClaim(TravelClaim $claim, User $viewer): array
    {
        return [
            'id' => $claim->id,
            'claim_number' => $claim->claim_number,
            'claim_month' => $claim->claim_month?->format('Y-m-01'),
            'claimant' => [
                'id' => $claim->claimant?->id,
                'name' => $claim->claimant_name,
                'department_name' => $claim->claimant?->department?->name,
                'address' => $claim->claimant_address,
            ],
            'checked_by' => $claim->checkedBy
                ? trim($claim->checkedBy->first_name.' '.$claim->checkedBy->last_name)
                : null,
            'approver' => $this->formatApproverName($claim),
            'vehicle' => [
                'make_model' => $claim->vehicle_make_model,
                'type' => $claim->vehicle_type,
                'year' => $claim->vehicle_year,
                'engine_volume' => $claim->engine_volume,
                'tariff_per_km' => (float) $claim->tariff_per_km,
                'home_distance_km' => (float) $claim->home_distance_km,
            ],
            'status' => $claim->status,
            'status_label' => Str::of($claim->status)->replace('_', ' ')->title()->value(),
            'approval_status' => $claim->approval_status,
            'approval_status_label' => Str::of($claim->approval_status)->replace('_', ' ')->title()->value(),
            'submitted_at' => $claim->submitted_at?->toDateTimeString(),
            'approval_decided_at' => $claim->approval_decided_at?->toDateTimeString(),
            'approval_comment' => $claim->approval_comment,
            'finance_received_at' => $claim->finance_received_at?->toDateTimeString(),
            'finance_paid_at' => $claim->finance_paid_at?->toDateTimeString(),
            'finance_comment' => $claim->finance_comment,
            'totals' => [
                'actual_distance_km' => (float) $claim->total_actual_distance_km,
                'claimable_distance_km' => (float) $claim->total_claimable_distance_km,
                'amount' => (float) $claim->total_amount,
            ],
            'trips' => $claim->trips->map(fn ($trip) => [
                'id' => $trip->id,
                'travel_date' => $trip->travel_date?->format('Y-m-d'),
                'route_from' => $trip->route_from,
                'route_to' => $trip->route_to,
                'start_time' => $trip->start_time,
                'end_time' => $trip->end_time,
                'nature_of_duty' => $trip->nature_of_duty,
                'actual_distance_km' => (float) $trip->actual_distance_km,
                'claimable_distance_km' => (float) $trip->claimable_distance_km,
                'line_total' => (float) $trip->line_total,
            ])->values()->all(),
            'permissions' => [
                'can_approve' => $this->canApprove($claim, $viewer) && $claim->approval_status === self::APPROVAL_PENDING,
                'can_reject_approval' => $this->canApprove($claim, $viewer) && $claim->approval_status === self::APPROVAL_PENDING,
                'can_receive' => ($viewer->can('domain.finance.view') || $viewer->can('domain.finance.manage'))
                    && $claim->approval_status === self::APPROVAL_APPROVED
                    && in_array($claim->status, ['submitted', 'received'], true),
                'can_pay' => ($viewer->can('domain.finance.view') || $viewer->can('domain.finance.manage'))
                    && $claim->approval_status === self::APPROVAL_APPROVED
                    && in_array($claim->status, ['submitted', 'received'], true),
                'can_reject' => ($viewer->can('domain.finance.view') || $viewer->can('domain.finance.manage'))
                    && in_array($claim->status, ['submitted', 'received'], true),
            ],
        ];
    }

    protected function assertClaimantScope(User $actor, StaffMember $actorStaff, StaffMember $claimant): void
    {
        $isSelf = (int) $claimant->id === (int) $actorStaff->id;

        if (! $isSelf) {
            throw new AuthorizationException('Travel claims must be submitted by the manager who is claiming.');
        }

        if (! $actorStaff->is_manager && ! $actorStaff->is_ceo) {
            throw new AuthorizationException('Only managers can submit travel claims.');
        }
    }

    public function canApprove(TravelClaim $claim, User $actor): bool
    {
        $actorStaff = $actor->staffMember;
        if (! $actorStaff || ! $claim->relationLoaded('claimant')) {
            $claim->loadMissing('claimant.manager.user');
            $actorStaff = $actor->staffMember;
        }

        if (! $actorStaff || ! $claim->claimant) {
            return false;
        }

        if ((int) $claim->claimant_staff_member_id === (int) $actorStaff->id) {
            return false;
        }

        if ((int) ($claim->claimant->manager_id ?? 0) === (int) $actorStaff->id) {
            return true;
        }

        return (bool) $actorStaff->is_ceo;
    }

    protected function resolveApprover(StaffMember $claimant): ?StaffMember
    {
        if ($claimant->manager && $claimant->manager->user_id) {
            return $claimant->manager;
        }

        return StaffMember::query()
            ->where('is_ceo', true)
            ->where('id', '!=', $claimant->id)
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->first();
    }

    protected function formatApproverName(TravelClaim $claim): ?string
    {
        if ($claim->approvedBy) {
            return (string) $claim->approvedBy->name;
        }

        $claim->loadMissing('claimant.manager');

        if ($claim->claimant?->manager) {
            return trim($claim->claimant->manager->first_name.' '.$claim->claimant->manager->last_name);
        }

        $ceo = StaffMember::query()
            ->where('is_ceo', true)
            ->whereNotNull('user_id')
            ->where('id', '!=', $claim->claimant_staff_member_id)
            ->orderBy('id')
            ->first();

        return $ceo ? trim($ceo->first_name.' '.$ceo->last_name) : null;
    }

    protected function generateClaimNumber(): string
    {
        $datePart = now()->format('Ym');
        $sequence = str_pad((string) ((int) TravelClaim::query()->count() + 1), 4, '0', STR_PAD_LEFT);

        return "TC-{$datePart}-{$sequence}";
    }
}
