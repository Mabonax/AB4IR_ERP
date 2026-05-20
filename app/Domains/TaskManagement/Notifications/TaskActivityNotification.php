<?php

namespace App\Domains\TaskManagement\Notifications;

use App\Domains\TaskManagement\Models\WorkTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected WorkTask $task,
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
            'type' => 'task_activity',
            'title' => $this->title,
            'message' => $this->message,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'status' => $this->task->status,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date?->format('Y-m-d'),
            'url' => '/task-management/tasks',
        ];
    }
}
