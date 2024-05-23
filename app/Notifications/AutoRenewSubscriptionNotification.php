<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutoRenewSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user_name;

    public function __construct($user_name)
    {
        $this->user_name = $user_name;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A Notification of Automatic Renewal Copy Trading Subscription')
            ->markdown('emails.auto_renew_notification', [
                'user_name' => $this->user_name,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
