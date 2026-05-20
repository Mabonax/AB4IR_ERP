<?php

namespace App\Domains\TaskManagement\Notifications;

use App\Domains\TaskManagement\Models\WorkTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskOverdueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected WorkTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_overdue',
            'title' => 'Task overdue reminder',
            'message' => sprintf('Task "%s" is overdue and still requires action.', $this->task->title),
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date?->format('Y-m-d'),
            'url' => '/task-management/tasks?overdue=1',
        ];
    }
}
