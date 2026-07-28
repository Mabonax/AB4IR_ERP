<?php

namespace App\Domains\Intelligence\Policies;

use App\Domains\Intelligence\Models\PromptTemplate;
use App\Models\User;

class PromptTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.intelligence.view') || $user->can('domain.intelligence.manage');
    }

    public function view(User $user, PromptTemplate $promptTemplate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.intelligence.manage');
    }

    public function update(User $user, PromptTemplate $promptTemplate): bool
    {
        return $user->can('domain.intelligence.manage');
    }
}
