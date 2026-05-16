<?php

namespace App\Domains\Beneficiaries\Policies;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Models\User;
use App\Policies\Concerns\InteractsWithDomainPermissions;

class BeneficiaryPolicy
{
    use InteractsWithDomainPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canViewDomain($user, 'beneficiaries');
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        return $this->canViewDomain($user, 'beneficiaries');
    }

    public function create(User $user): bool
    {
        return $this->canManageDomain($user, 'beneficiaries');
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return $this->canManageDomain($user, 'beneficiaries');
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $this->canManageDomain($user, 'beneficiaries');
    }
}
