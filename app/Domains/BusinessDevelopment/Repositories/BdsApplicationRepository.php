<?php

namespace App\Domains\BusinessDevelopment\Repositories;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BdsApplicationRepository implements BdsApplicationRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return BdsApplication::with(['province', 'assessor', 'updatedBy'])
            ->withExists([
                'adjudications as has_submitted_adjudication' => fn ($query) => $query->where('status', 'submitted'),
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): ?BdsApplication
    {
        return BdsApplication::with(['province', 'assessor', 'updatedBy'])
            ->withExists([
                'adjudications as has_submitted_adjudication' => fn ($query) => $query->where('status', 'submitted'),
            ])
            ->find($id);
    }

    public function create(array $data): BdsApplication
    {
        return BdsApplication::create($data);
    }

    public function update(BdsApplication $application, array $data): BdsApplication
    {
        $application->update($data);

        return $application;
    }
}
