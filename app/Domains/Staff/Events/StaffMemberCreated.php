<?php

namespace App\Domains\Staff\Events;

use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffMemberCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StaffMember $staff,
        public User $user,
    ) {}
}
