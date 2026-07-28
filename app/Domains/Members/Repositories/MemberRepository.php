<?php

namespace App\Domains\Members\Repositories;

use App\Domains\Members\Interfaces\MemberRepositoryInterface;
use App\Domains\Members\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberRepository implements MemberRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Member::query()
            ->with([
                'province',
                'municipality',
                'region',
                'township',
                'ward',
                'branch',
                'employmentProfile',
                'qualifications',
                'skills',
            ])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['member_type'] ?? null, fn ($query, $type) => $query->where('member_type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['township_id'] ?? null, fn ($query, $townshipId) => $query->where('township_id', $townshipId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();
    }

    public function find(int $id): ?Member
    {
        return Member::query()
            ->with([
                'province',
                'municipality',
                'region',
                'township',
                'ward',
                'branch',
                'employmentProfile',
                'qualifications',
                'skills',
                'workExperiences',
                'opportunityInterests',
                'assignments.assignable',
            ])
            ->find($id);
    }

    public function create(array $payload): Member
    {
        return Member::query()->create($payload);
    }

    public function update(Member $member, array $payload): Member
    {
        $member->update($payload);

        return $member->refresh();
    }
}
