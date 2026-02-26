<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Policies;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Models\User;

class AdjudicationAssessmentPolicy
{
    protected function isAdmin(User $user): bool
    {
        return method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super-admin', 'super admin', 'admin']);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('domain.business-development.view')
            || $user->can('domain.business-development.manage');
    }

    public function view(User $user, AdjudicationAssessment $assessment): bool
    {
        if ($user->can('domain.business-development.manage')) {
            return true;
        }

        return $assessment->judge_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('domain.business-development.manage');
    }

    public function update(User $user, AdjudicationAssessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return $assessment->status === 'draft';
        }

        return $assessment->status === 'draft'
            && $assessment->judge_id === (int) $user->id
            && $user->can('domain.business-development.manage');
    }

    public function submit(User $user, AdjudicationAssessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return $assessment->status === 'draft';
        }

        return $assessment->status === 'draft'
            && $assessment->judge_id === (int) $user->id
            && $user->can('domain.business-development.manage');
    }

    public function delete(User $user, AdjudicationAssessment $assessment): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $assessment->status === 'draft' && $assessment->judge_id === (int) $user->id;
    }

    public function unlock(User $user, AdjudicationAssessment $assessment): bool
    {
        return $user->can('domain.business-development.manage')
            && $this->isAdmin($user)
            && $assessment->status === 'submitted';
    }
}
