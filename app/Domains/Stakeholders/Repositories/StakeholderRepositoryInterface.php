<?php

namespace App\Domains\Stakeholders\Repositories;

use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Stakeholders\Models\StakeholderContact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StakeholderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Stakeholder;

    public function create(array $data): Stakeholder;

    public function update(Stakeholder $stakeholder, array $data): Stakeholder;

    public function delete(Stakeholder $stakeholder): bool;

    public function createContact(Stakeholder $stakeholder, array $data): StakeholderContact;

    public function updateContact(Stakeholder $stakeholder, array $data): StakeholderContact;
}
