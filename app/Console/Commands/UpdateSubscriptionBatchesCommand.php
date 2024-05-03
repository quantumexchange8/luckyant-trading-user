<?php

namespace App\Console\Commands;

use App\Models\SubscriptionBatch;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateSubscriptionBatchesCommand extends Command
{
    protected $signature = 'update:subscription-batches';

    protected $description = 'Update settlement date for subscription batches';

    public function handle(): void
    {
        $yesterday = Carbon::yesterday()->endOfDay();

        $subscription_batches = SubscriptionBatch::where('status', 'Active')
            ->where('settlement_date', '<', now())
            ->whereDate('settlement_date', '>', $yesterday->copy()->subDay())
            ->get();

        foreach ($subscription_batches as $subscription_batch) {
            $start_date = $subscription_batch->settlement_date;
            $end_date = $start_date->addDays($subscription_batch->subscription_period)->endOfDay();

            $subscription_batch->update([
                'settlement_start_date' => $start_date,
                'settlement_date' => $end_date,
            ]);
        }

        //ToDo: Update batch log
    }
}
