<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use Illuminate\Console\Command;

class TerminateStopRenewalCommand extends Command
{
    protected $signature = 'terminate:stop-renewal';

    protected $description = 'Command description';

    public function handle(): void
    {
        $subscriptions = Subscription::where('status', 'Expiring')
            ->whereDate('expired_date', '<', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => 'Terminated',
                'termination_date' => now(),
            ]);

            $subscriber = Subscriber::where('meta_login', $subscription->meta_login)->first();
            $subscriber->update([
                'status' => 'Terminated',
                'unsubscribe_date' => now(),
            ]);

            $subscription_batches = SubscriptionBatch::where('subscription_id', $subscription->id)->get();
            foreach ($subscription_batches as $batch) {
                $batch->update([
                    'status' => 'Terminated',
                    'termination_date' => now(),
                ]);
            }
        }
    }
}
