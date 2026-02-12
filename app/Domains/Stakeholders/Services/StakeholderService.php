<?php

namespace App\Domains\Stakeholders\Services;

use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Stakeholders\Repositories\StakeholderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class StakeholderService
{
    public function __construct(
        protected StakeholderRepositoryInterface $repository
    ) {}

    public function paginateStakeholders(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getStakeholderById(int $id): Stakeholder
    {
        $stakeholder = $this->repository->find($id);

        if (! $stakeholder) {
            throw new ModelNotFoundException('Stakeholder not found.');
        }

        return $stakeholder;
    }

    protected function hasFilledContact(array $contact): bool
    {
        foreach (['full_name', 'email', 'contact_number', 'position'] as $field) {
            $value = $contact[$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractContacts(array $data): array
    {
        if (! empty($data['contacts']) && is_array($data['contacts'])) {
            return array_values(array_filter(
                $data['contacts'],
                fn ($contact) => is_array($contact) && $this->hasFilledContact($contact)
            ));
        }

        if (! empty($data['contact']) && is_array($data['contact']) && $this->hasFilledContact($data['contact'])) {
            return [$data['contact']];
        }

        return [];
    }

    public function createStakeholderWithContact(array $data): Stakeholder
    {
        return DB::transaction(function () use ($data) {
            $stakeholder = $this->repository->create($data['stakeholder']);
            $contacts = $this->extractContacts($data);
            if (! empty($contacts)) {
                $this->repository->createContacts($stakeholder, $contacts);
            }

            return $this->repository->find($stakeholder->id) ?? $stakeholder;
        });
    }

    public function updateStakeholderWithContact(int $id, array $data): Stakeholder
    {
        return DB::transaction(function () use ($id, $data) {
            $stakeholder = $this->getStakeholderById($id);

            $this->repository->update($stakeholder, $data['stakeholder']);

            $contacts = $this->extractContacts($data);
            foreach ($contacts as $contact) {
                $this->repository->updateContact($stakeholder, $contact);
            }

            return $this->repository->find($stakeholder->id) ?? $stakeholder;
        });
    }

    public function addStakeholderContact(int $stakeholderId, array $contactData): Stakeholder
    {
        return DB::transaction(function () use ($stakeholderId, $contactData) {
            $stakeholder = $this->getStakeholderById($stakeholderId);
            $this->repository->createContact($stakeholder, $contactData);

            return $this->repository->find($stakeholder->id) ?? $stakeholder;
        });
    }

    public function deleteStakeholderContact(int $stakeholderId, int $contactId): Stakeholder
    {
        return DB::transaction(function () use ($stakeholderId, $contactId) {
            $stakeholder = $this->getStakeholderById($stakeholderId);
            $this->repository->deleteContact($stakeholder, $contactId);

            return $this->repository->find($stakeholder->id) ?? $stakeholder;
        });
    }

    public function deleteStakeholder(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $stakeholder = $this->getStakeholderById($id);
            return $this->repository->delete($stakeholder);
        });
    }
}
