<?php

namespace App\Domains\TaskManagement\Notifications;

use App\Domains\TaskManagement\Models\WorkTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected WorkTask $task,
        protected string $context,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'title' => 'Task assignment updated',
            'message' => $this->context,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'status' => $this->task->status,
            'priority' => $this->task->priority,
            'due_date' => $this->task->due_date?->format('Y-m-d'),
            'url' => '/task-management/tasks',
        ];
    }
}
