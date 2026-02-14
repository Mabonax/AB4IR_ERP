<?php

namespace App\Domains\BusinessDevelopment\Repositories;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BdsApplicationRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?BdsApplication;

    public function create(array $data): BdsApplication;

    public function update(BdsApplication $application, array $data): BdsApplication;
}

