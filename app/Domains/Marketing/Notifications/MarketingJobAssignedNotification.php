<?php

namespace App\Domains\Marketing\Notifications;

use App\Domains\Marketing\Models\MarketingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MarketingJobAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MarketingJob $job,
        protected string $context,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketing_job_assigned',
            'title' => 'Marketing work assignment updated',
            'message' => $this->context,
            'marketing_job_id' => $this->job->id,
            'marketing_job_title' => $this->job->title,
            'status' => $this->job->status,
            'priority' => $this->job->priority,
            'job_type' => $this->job->job_type,
            'url' => "/marketing/jobs/{$this->job->id}",
        ];
    }
}
