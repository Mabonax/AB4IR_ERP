<?php

namespace App\Domains\Members\Interfaces;

use App\Domains\Members\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function find(int $id): ?Member;

    public function create(array $payload): Member;

    public function update(Member $member, array $payload): Member;
}
