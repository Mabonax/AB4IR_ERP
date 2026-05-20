<?php

namespace App\Domains\TaskManagement\Jobs;

use App\Domains\TaskManagement\Services\SupportTicketService;
use App\Domains\TaskManagement\Services\WorkTaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTaskManagementReminderNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WorkTaskService $taskService, SupportTicketService $ticketService): void
    {
        $taskService->sendOverdueReminders();
        $ticketService->sendOverdueReminders();
    }
}
