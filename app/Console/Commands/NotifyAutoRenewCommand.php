<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\AutoRenewSubscriptionNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyAutoRenewCommand extends Command
{
    protected $signature = 'notify:auto-renew';

    protected $description = 'A Notification of Automatic Renewal Copy Trading Subscription';

    public function handle(): void
    {
        $subscriptions = Subscription::where('status', 'Active')
            ->whereBetween('expired_date', [Carbon::now()->addDay(), Carbon::now()->addDay()->endOfDay()])
            ->get();

        foreach ($subscriptions as $subscription) {
            Notification::route('mail', $subscription->user->email)
                ->notify(new AutoRenewSubscriptionNotification($subscription->user->name));
        }

    }
}
