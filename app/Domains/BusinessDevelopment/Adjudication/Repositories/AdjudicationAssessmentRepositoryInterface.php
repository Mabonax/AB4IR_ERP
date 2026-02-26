<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Repositories;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdjudicationAssessmentRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): AdjudicationAssessment;

    public function update(AdjudicationAssessment $assessment, array $data): AdjudicationAssessment;

    public function delete(AdjudicationAssessment $assessment): void;
}
