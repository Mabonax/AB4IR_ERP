<?php

namespace App\Domains\TaskManagement\Notifications;

use App\Domains\TaskManagement\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SupportTicket $ticket,
        protected string $context,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_assigned',
            'title' => 'Support ticket assignment updated',
            'message' => $this->context,
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'status' => $this->ticket->status,
            'priority' => $this->ticket->priority,
            'url' => '/task-management/tickets',
        ];
    }
}
