<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PammDailyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $pamm;
    public function __construct($user, $pamm)
    {
        $this->user = $user;
        $this->pamm = $pamm;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Daily Report for PAMM Subscription')
            ->markdown('emails.pamm_daily_report', [
                'user' => $this->user,
                'pamm' => $this->pamm,
            ]);

    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
