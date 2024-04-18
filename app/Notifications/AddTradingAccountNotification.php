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
        // return (new MailMessage)
            // ->subject('LuckyAnt Trading - New MT5 Live Account Added')
            // ->greeting('Dear ' . $this->user->name)
            // ->line('Welcome aboard LuckyAnt Trading! We are excited to have you as a member of our trading community.')
            // ->line('Please find the credentials below if you need to login on MetaTrader 5.')
            // ->line('Login: ' . $this->metaAccount['login'])
            // ->line('Main Password: ' . $this->metaAccount['mainPassword'])
            // ->line('Investor Password: ' . $this->metaAccount['investPassword'])
            // ->line('Thank you for using our application!');
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
