<?php

namespace App\Domains\BusinessDevelopment\Repositories;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BdsIncubateeRepository implements BdsIncubateeRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return BdsIncubatee::query()
            ->with('province')
            ->when($search, function ($query, $searchTerm) {
                $searchTerm = trim((string) $searchTerm);
                $query->where(function ($nested) use ($searchTerm) {
                    $nested
                        ->where('full_name', 'like', "%{$searchTerm}%")
                        ->orWhere('company_name', 'like', "%{$searchTerm}%")
                        ->orWhere('id_number', 'like', "%{$searchTerm}%")
                        ->orWhere('company_registration_number', 'like', "%{$searchTerm}%")
                        ->orWhere('mobile_number', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?BdsIncubatee
    {
        return BdsIncubatee::with('province')->find($id);
    }

    public function create(array $data): BdsIncubatee
    {
        return BdsIncubatee::create($data);
    }

    public function update(BdsIncubatee $incubatee, array $data): BdsIncubatee
    {
        $incubatee->update($data);

        return $incubatee;
    }

    public function delete(BdsIncubatee $incubatee): bool
    {
        return $incubatee->delete();
    }
}
