<?php

namespace App\Domains\Meetings\Policies;

use App\Domains\Meetings\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.meetings.view') || $user->can('domain.meetings.manage');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('domain.meetings.manage');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->can('domain.meetings.manage');
    }
}
