<?php

namespace App\Domains\Events\Notifications;

use App\Domains\Events\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventLifecycleNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Event $event,
        protected string $title,
        protected string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event_lifecycle',
            'title' => $this->title,
            'message' => $this->message,
            'event_id' => $this->event->id,
            'url' => '/events/'.$this->event->id,
        ];
    }
}
