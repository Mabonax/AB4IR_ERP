<?php

namespace App\Domains\Stakeholders\Repositories;

use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Stakeholders\Models\StakeholderContact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StakeholderRepository implements StakeholderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Stakeholder::with('contact')->latest()->paginate($perPage);
    }

    public function find(int $id): ?Stakeholder
    {
        return Stakeholder::with('contact')->find($id);
    }

    public function create(array $data): Stakeholder
    {
        return Stakeholder::create($data);
    }

    public function update(Stakeholder $stakeholder, array $data): Stakeholder
    {
        $stakeholder->update($data);
        return $stakeholder;
    }

    public function delete(Stakeholder $stakeholder): bool
    {
        return $stakeholder->delete();
    }

    public function createContact(Stakeholder $stakeholder, array $data): StakeholderContact
    {
        return $stakeholder->contact()->create($data);
    }

    public function updateContact(Stakeholder $stakeholder, array $data): StakeholderContact
    {
        if ($stakeholder->contact) {
            $stakeholder->contact->update($data);
            return $stakeholder->contact;
        }

        return $stakeholder->contact()->create($data);
    }
}
