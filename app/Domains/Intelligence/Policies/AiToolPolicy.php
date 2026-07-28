<?php

namespace App\Domains\Intelligence\Policies;

use App\Domains\Intelligence\Models\AiTool;
use App\Models\User;

class AiToolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.intelligence.view') || $user->can('domain.intelligence.manage');
    }

    public function view(User $user, AiTool $aiTool): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.intelligence.manage');
    }

    public function update(User $user, AiTool $aiTool): bool
    {
        return $user->can('domain.intelligence.manage');
    }
}
