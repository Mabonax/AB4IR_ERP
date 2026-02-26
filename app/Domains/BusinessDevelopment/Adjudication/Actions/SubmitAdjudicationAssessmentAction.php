<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Actions;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Domains\BusinessDevelopment\Adjudication\Services\AdjudicationAssessmentService;
use App\Models\User;

class SubmitAdjudicationAssessmentAction
{
    public function __construct(
        protected AdjudicationAssessmentService $service
    ) {}

    public function execute(AdjudicationAssessment $assessment, User $actor): AdjudicationAssessment
    {
        return $this->service->submit($assessment, $actor);
    }
}
