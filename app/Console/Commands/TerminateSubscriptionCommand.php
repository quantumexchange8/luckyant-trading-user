<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\SubscriptionsSchedulerLog;
use Illuminate\Console\Command;

class TerminateSubscriptionCommand extends Command
{
    protected $signature = 'terminate:subscription';

    protected $description = 'Terminate expiring subscription';

    public function handle(): void
    {
        $subscriptions = Subscription::where('status', 'Active')
            ->where('auto_renewal', 0)
            ->whereDate('expired_date', '<', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            Subscriber::where('subscription_id', $subscription->id)->first()->update([
                'status' => 'Unsubscribed',
                'auto_renewal' => false
            ]);

            $subscription->update([
                'status' => 'Terminated',
            ]);

            $subscription_batches = SubscriptionBatch::where('subscription_id', $subscription->id)
                ->where('status', 'Expiring')
                ->get();

            foreach ($subscription_batches as $subscription_batch) {
                $subscription_batch->update([
                    'termination_date' => now(),
                    'status' => 'Terminated'
                ]);
            }
        }
    }
}
