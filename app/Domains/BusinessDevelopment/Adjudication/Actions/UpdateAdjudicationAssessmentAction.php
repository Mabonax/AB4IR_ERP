<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Actions;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Adjudication\Services\AdjudicationAssessmentService;
use App\Models\User;

class UpdateAdjudicationAssessmentAction
{
    public function __construct(
        protected AdjudicationAssessmentService $service
    ) {}

    public function execute(AdjudicationAssessment $assessment, array $data, User $actor): AdjudicationAssessment
    {
        return $this->service->updateDraft($assessment, $data, $actor);
    }
}
