<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Repositories;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAdjudicationAssessmentRepository implements AdjudicationAssessmentRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = AdjudicationAssessment::query()
            ->with(['judge:id,name', 'smme:id,company_name', 'scores'])
            ->latest();

        if (! $user->can('domain.business-development.manage')) {
            $query->where('judge_id', (int) $user->id);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): AdjudicationAssessment
    {
        return AdjudicationAssessment::create($data);
    }

    public function update(AdjudicationAssessment $assessment, array $data): AdjudicationAssessment
    {
        $assessment->update($data);

        return $assessment;
    }

    public function delete(AdjudicationAssessment $assessment): void
    {
        $assessment->delete();
    }
}
