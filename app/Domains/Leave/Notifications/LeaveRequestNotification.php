<?php

namespace App\Domains\Leave\Notifications;

use App\Domains\Leave\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeaveRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected LeaveRequest $leave,
        protected string $title,
        protected string $message,
        protected string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_request',
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'leave_request_id' => $this->leave->id,
            'staff_member_id' => $this->leave->staff_member_id,
            'staff_member_name' => $this->leave->staffMember
                ? trim($this->leave->staffMember->first_name.' '.$this->leave->staffMember->last_name)
                : null,
            'manager_id' => $this->leave->manager_id,
            'leave_type' => $this->leave->leave_type,
            'status' => $this->leave->status,
            'start_date' => $this->leave->start_date?->format('Y-m-d'),
            'end_date' => $this->leave->end_date?->format('Y-m-d'),
            'total_days' => (float) $this->leave->total_days,
            'url' => '/leave-requests',
        ];
    }
}
