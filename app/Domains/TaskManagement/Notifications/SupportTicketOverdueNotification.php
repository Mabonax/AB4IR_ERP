<?php

namespace App\Domains\TaskManagement\Notifications;

use App\Domains\TaskManagement\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SupportTicket $ticket,
        protected int $ageHours,
        protected int $slaTargetHours,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_overdue',
            'title' => 'Support ticket overdue',
            'message' => sprintf('Ticket "%s" has exceeded SLA at %dh against a %dh target.', $this->ticket->title, $this->ageHours, $this->slaTargetHours),
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'priority' => $this->ticket->priority,
            'url' => '/task-management/tickets?overdue=1',
        ];
    }
}
