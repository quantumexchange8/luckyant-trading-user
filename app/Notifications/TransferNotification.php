<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $amount;
    protected $fromWallet;
    protected $toWallet;

    public function __construct($amount, $fromWallet, $toWallet)
    {
        $this->amount = $amount;
        $this->fromWallet = $fromWallet;
        $this->toWallet = $toWallet;
    }

    public function via($notifiable)
    {
        return ['mail']; // Send notification via mail
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Transfer Notification')
            ->greeting('Hello ' . $notifiable->username  . ',')
            ->line('You have received a transfer of $' . number_format($this->amount, 2) . ' from ' . $this->fromWallet->wallet_address  . '.');
    }

    // public function toDatabase($notifiable)
    // {
    //     return [
    //         'title' => 'Transfer Notification',
    //         'content' => 'You have received a transfer of $' . number_format($this->amount, 2) . ' from ' . $this->fromWallet->wallet_address  . '.',
    //         'post_date' => now()->toDateTimeString(),
    //     ];
    // }

    // public function toArray($notifiable)
    // {
    //     return [
    //         'title' => 'Transfer Notification',
    //         'content' => 'You have received a transfer of $' . number_format($this->amount, 2) . ' from ' . $this->fromWallet->wallet_address  . '.',
    //         'post_date' => now()->toDateTimeString(),
    //     ];
    // }
}
