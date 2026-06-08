<?php

namespace App\Domains\Staff\Notifications;

use App\Domains\Staff\Models\StaffMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffSystemAccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected StaffMember $staff,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = route('login');
        $fullName = trim($this->staff->first_name.' '.$this->staff->last_name);

        return (new MailMessage)
            ->subject('Your system access is ready')
            ->greeting('Hello '.$fullName.',')
            ->line('Your staff profile has been created on the system.')
            ->line('Use the link below to access the platform and sign in with your assigned credentials.')
            ->action('Open the system', $loginUrl)
            ->line('If you do not yet have your password, please contact the system administrator or use the password reset flow once it is enabled for your account.');
    }
}
