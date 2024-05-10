<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $otp;

    public function __construct($otp) {
        $this->otp = $otp;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('One - Time Password Notification')
            ->markdown('emails.otp_verification_email', ['otp' => $this->otp]);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
