<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddTradingAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $metaAccount;
    protected $balance;
    protected $user;
    public function __construct($metaAccount, $balance, $user)
    {
        $this->metaAccount = $metaAccount;
        $this->balance = $balance;
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('MT5 Account Activation Confirmed')
            ->markdown('emails.mt5_activation_email', [
                'user' => $this->user,
                'balance' => $this->balance,
                'metaAccount' => $this->metaAccount,
            ]);

    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
