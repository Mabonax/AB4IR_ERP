<?php

namespace App\Domains\Staff\Listeners;

use App\Domains\Staff\Events\StaffMemberCreated;
use App\Domains\Staff\Notifications\StaffSystemAccessNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendStaffSystemAccessEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(StaffMemberCreated $event): void
    {
        if (! config('staff.send_welcome_notification', true)) {
            return;
        }

        $event->user->notify(new StaffSystemAccessNotification($event->staff));
    }
}
