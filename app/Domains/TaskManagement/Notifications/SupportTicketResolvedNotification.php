<?php

namespace App\Domains\TaskManagement\Notifications;

use App\Domains\TaskManagement\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_resolved',
            'title' => 'Support ticket resolved',
            'message' => sprintf('Ticket "%s" has been marked resolved.', $this->ticket->title),
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'resolution_summary' => $this->ticket->resolution_summary,
            'url' => '/task-management/tickets',
        ];
    }
}
