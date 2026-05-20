<?php

namespace App\Domains\Stakeholders\Repositories;

use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Stakeholders\Models\StakeholderContact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StakeholderRepository implements StakeholderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Stakeholder::with(['contact', 'contacts'])->latest()->paginate($perPage);
    }

    public function find(int $id): ?Stakeholder
    {
        return Stakeholder::with(['contact', 'contacts'])->find($id);
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
        return $stakeholder->contacts()->create($data);
    }

    public function updateContact(Stakeholder $stakeholder, array $data): StakeholderContact
    {
        if (isset($data['id'])) {
            $contact = $stakeholder->contacts()->whereKey((int) $data['id'])->first();
            if ($contact) {
                $contact->update($data);

                return $contact;
            }
        }

        if ($stakeholder->contact) {
            $stakeholder->contact->update($data);

            return $stakeholder->contact;
        }

        return $stakeholder->contacts()->create($data);
    }

    public function createContacts(Stakeholder $stakeholder, array $contacts): void
    {
        foreach ($contacts as $contact) {
            $stakeholder->contacts()->create($contact);
        }
    }

    public function deleteContact(Stakeholder $stakeholder, int $contactId): bool
    {
        return (bool) $stakeholder->contacts()->whereKey($contactId)->delete();
    }
}
