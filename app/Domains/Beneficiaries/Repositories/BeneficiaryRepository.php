<?php

namespace App\Domains\Beneficiaries\Repositories;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BeneficiaryRepository implements BeneficiaryRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Beneficiary::with('nextOfKin')->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Beneficiary::with('nextOfKin')->latest()->get();
    }

    public function find(int $id): ?Beneficiary
    {
        return Beneficiary::with('nextOfKin')->find($id);
    }

    public function create(array $data): Beneficiary
    {
        return Beneficiary::create($data);
    }

    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        $beneficiary->update($data);
        return $beneficiary;
    }

    public function delete(Beneficiary $beneficiary): bool
    {
        return $beneficiary->delete();
    }
}
