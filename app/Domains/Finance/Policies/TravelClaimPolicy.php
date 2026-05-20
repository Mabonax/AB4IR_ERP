<?php

namespace App\Domains\Finance\Policies;

use App\Domains\Finance\Models\TravelClaim;
use App\Models\User;

class TravelClaimPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.finance.view')
            || $user->can('domain.finance.manage')
            || $user->can('travel-claims.submit');
    }

    public function view(User $user, TravelClaim $claim): bool
    {
        if ($user->can('domain.finance.view') || $user->can('domain.finance.manage')) {
            return true;
        }

        $staff = $user->staffMember;
        if (! $staff) {
            return false;
        }

        return (int) $claim->submitted_by_user_id === (int) $user->id
            || (int) $claim->claimant_staff_member_id === (int) $staff->id
            || (int) $claim->claimant?->manager_id === (int) $staff->id;
    }

    public function create(User $user): bool
    {
        $staff = $user->staffMember;

        return $user->can('travel-claims.submit')
            && $staff
            && ((bool) $staff->is_manager || (bool) $staff->is_ceo);
    }

    public function receive(User $user, TravelClaim $claim): bool
    {
        return $user->can('domain.finance.view') || $user->can('domain.finance.manage');
    }

    public function approve(User $user, TravelClaim $claim): bool
    {
        $staff = $user->staffMember;
        if (! $staff || ! $claim->claimant) {
            $claim->loadMissing('claimant');
            $staff = $user->staffMember;
        }

        if (! $staff || ! $claim->claimant) {
            return false;
        }

        if ((int) $claim->claimant_staff_member_id === (int) $staff->id) {
            return false;
        }

        if ((int) ($claim->claimant->manager_id ?? 0) === (int) $staff->id) {
            return true;
        }

        return (bool) $staff->is_ceo;
    }

    public function pay(User $user, TravelClaim $claim): bool
    {
        return $user->can('domain.finance.view') || $user->can('domain.finance.manage');
    }

    public function reject(User $user, TravelClaim $claim): bool
    {
        return $user->can('domain.finance.view') || $user->can('domain.finance.manage');
    }
}
