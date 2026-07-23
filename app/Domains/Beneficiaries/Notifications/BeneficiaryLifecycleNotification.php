<?php

namespace App\Domains\Beneficiaries\Notifications;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BeneficiaryLifecycleNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Beneficiary $beneficiary,
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
            'type' => 'beneficiary_lifecycle',
            'title' => $this->title,
            'message' => $this->message,
            'beneficiary_id' => $this->beneficiary->id,
            'url' => '/beneficiaries/'.$this->beneficiary->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message);
    }
}
