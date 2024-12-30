<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $transaction;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $user = User::find($this->transaction->user_id);
        $transaction_type = $this->transaction->transaction_type == 'Deposit' ? 'Deposit' : 'Balance In';
        if ($transaction_type == 'Deposit') {
            $to = 'Cash Wallet';
        } else {
            $to = $this->transaction->to_meta_login;
        }

        return (new MailMessage)
            ->subject($transaction_type . ' Pending Approval - ' . $this->transaction->transaction_number)
            ->greeting($transaction_type . ' Pending Approval - ' . $this->transaction->transaction_number)
            ->line('Email: ' . $user->email)
            ->line('Name: ' . $user->name)
            ->line('To: ' . $to)
            ->line('Amount: $' . number_format($this->transaction->amount, 2))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
