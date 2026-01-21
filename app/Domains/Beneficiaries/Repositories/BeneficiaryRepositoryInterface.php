<?php

namespace App\Domains\Beneficiaries\Repositories;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Support\Collection;

interface BeneficiaryRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Beneficiary;

    public function create(array $data): Beneficiary;

    public function update(Beneficiary $beneficiary, array $data): Beneficiary;

    public function delete(Beneficiary $beneficiary): bool;
}
