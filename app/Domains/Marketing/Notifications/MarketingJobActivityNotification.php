<?php

namespace App\Domains\Marketing\Notifications;

use App\Domains\Marketing\Models\MarketingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MarketingJobActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MarketingJob $job,
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
            'type' => 'marketing_job_activity',
            'title' => $this->title,
            'message' => $this->message,
            'marketing_job_id' => $this->job->id,
            'marketing_job_title' => $this->job->title,
            'status' => $this->job->status,
            'priority' => $this->job->priority,
            'job_type' => $this->job->job_type,
            'url' => "/marketing/jobs/{$this->job->id}",
        ];
    }
}
