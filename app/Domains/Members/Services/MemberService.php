<?php

namespace App\Domains\Members\Services;

use App\Domains\Committees\Models\Committee;
use App\Domains\Geography\Models\Branch;
use App\Domains\Geography\Models\Region;
use App\Domains\Governance\Models\GovernanceStructure;
use App\Domains\Members\Interfaces\MemberRepositoryInterface;
use App\Domains\Members\Models\Member;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class MemberService
{
    public function __construct(
        protected MemberRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function findOrFail(int $id): Member
    {
        $member = $this->repository->find($id);

        if (! $member) {
            throw new ModelNotFoundException('Member not found.');
        }

        return $member;
    }

    public function create(array $payload): Member
    {
        return DB::transaction(function () use ($payload) {
            $member = $this->repository->create($this->memberPayload($payload));
            $this->syncRelations($member, $payload);

            return $member->refresh();
        });
    }

    public function update(int $id, array $payload): Member
    {
        return DB::transaction(function () use ($id, $payload) {
            $member = $this->repository->update($this->findOrFail($id), $this->memberPayload($payload));
            $this->syncRelations($member, $payload);

            return $member->refresh();
        });
    }

    protected function memberPayload(array $payload): array
    {
        return collect($payload)->except([
            'employment',
            'qualifications',
            'skills',
            'work_experiences',
            'interests',
            'assignments',
        ])->all();
    }

    protected function syncRelations(Member $member, array $payload): void
    {
        $employment = $payload['employment'] ?? null;
        if (is_array($employment) && filled($employment['employment_status'] ?? null)) {
            $member->employmentProfile()->updateOrCreate(
                ['member_id' => $member->id],
                $employment
            );
        } else {
            $member->employmentProfile()?->delete();
        }

        $member->qualifications()->delete();
        foreach ($payload['qualifications'] ?? [] as $qualification) {
            if (blank($qualification['qualification_name'] ?? null)) {
                continue;
            }

            $member->qualifications()->create($qualification);
        }

        $member->skills()->delete();
        foreach ($payload['skills'] ?? [] as $skill) {
            if (blank($skill['skill_name'] ?? null)) {
                continue;
            }

            $member->skills()->create($skill);
        }

        $member->workExperiences()->delete();
        foreach ($payload['work_experiences'] ?? [] as $experience) {
            if (blank($experience['employer'] ?? null) && blank($experience['position'] ?? null)) {
                continue;
            }

            $member->workExperiences()->create($experience);
        }

        $member->opportunityInterests()->delete();
        foreach ($payload['interests'] ?? [] as $interest) {
            if (blank($interest['interest_type'] ?? null)) {
                continue;
            }

            $member->opportunityInterests()->create($interest);
        }

        $member->assignments()->delete();
        foreach ($payload['assignments'] ?? [] as $assignment) {
            if (blank($assignment['assignment_type'] ?? null) || blank($assignment['assignable_id'] ?? null)) {
                continue;
            }

            $assignableClass = $this->assignmentClass((string) $assignment['assignment_type']);
            $assignable = $assignableClass::query()->find($assignment['assignable_id']);

            if (! $assignable) {
                continue;
            }

            $member->assignments()->create([
                'assignment_type' => $assignment['assignment_type'],
                'assignable_type' => $assignableClass,
                'assignable_id' => $assignable->getKey(),
                'member_role' => $assignment['member_role'] ?? null,
                'started_at' => $assignment['started_at'] ?? null,
                'ended_at' => $assignment['ended_at'] ?? null,
                'notes' => $assignment['notes'] ?? null,
            ]);
        }
    }

    protected function assignmentClass(string $type): string
    {
        return match ($type) {
            'governance_structure' => GovernanceStructure::class,
            'committee' => Committee::class,
            'branch' => Branch::class,
            'region' => Region::class,
            'program' => Program::class,
            'project' => Project::class,
            default => throw new \InvalidArgumentException("Unsupported assignment type [{$type}]."),
        };
    }

    public function assignmentOptions(): array
    {
        return [
            'governance_structure' => GovernanceStructure::query()->orderBy('name')->get(['id', 'name']),
            'committee' => Committee::query()->orderBy('name')->get(['id', 'name']),
            'branch' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'region' => Region::query()->orderBy('name')->get(['id', 'name']),
            'program' => Program::query()->orderBy('title')->get(['id', 'title']),
            'project' => Project::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
