<?php

namespace App\Domains\Beneficiaries\Repositories;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BeneficiaryRepositoryInterface
{
    public function paginate(?int $projectId = null, int $perPage = 15): LengthAwarePaginator;

    public function all(): Collection;

    public function find(int $id): ?Beneficiary;

    public function create(array $data): Beneficiary;

    public function update(Beneficiary $beneficiary, array $data): Beneficiary;

    public function delete(Beneficiary $beneficiary): bool;
}
