<?php

namespace App\Domains\Intelligence\Policies;

use App\Domains\Intelligence\Models\MemoryRecord;
use App\Models\User;

class MemoryRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.intelligence.view') || $user->can('domain.intelligence.manage');
    }

    public function view(User $user, MemoryRecord $memoryRecord): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.intelligence.manage');
    }

    public function update(User $user, MemoryRecord $memoryRecord): bool
    {
        return $user->can('domain.intelligence.manage');
    }
}
