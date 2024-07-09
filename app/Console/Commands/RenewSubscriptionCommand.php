<?php

namespace App\Console\Commands;

use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\SubscriptionsSchedulerLog;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use Illuminate\Console\Command;

class RenewSubscriptionCommand extends Command
{
    protected $signature = 'renew:subscription';

    protected $description = 'Renew the subscriptions';

    public function handle(): void
    {
        $subscriptions = Subscription::where('status', 'Active')
            ->where('auto_renewal', 1)
            ->whereDate('expired_date', '<', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = User::find($subscription->user_id);
            $subscriber = Subscriber::where('meta_login', $subscription->meta_login)->first();
            $subscription_batches = SubscriptionBatch::where('subscription_id', $subscription->id)->get();

            $subscription->update([
                'status' => 'Expired'
            ]);
            $expiredDate = $subscription->expired_date;
            $carbonExpiredDate = \Carbon\Carbon::parse($expiredDate);
            $calculatedDay = $carbonExpiredDate->addDays($subscription->subscription_period)->endOfDay();

            $newSubscription = null;
            if ($subscription->auto_renewal) {
                $newSubscription = Subscription::create([
                    'user_id' => $subscription->user_id,
                    'trading_account_id' => $subscription->trading_account_id,
                    'meta_login' => $subscription->meta_login,
                    'meta_balance' => $subscription->meta_balance,
                    'transaction_id' => $subscription->transaction_id,
                    'master_id' => $subscription->master_id,
                    'subscription_number' => RunningNumberService::getID('subscription'),
                    'subscription_period' => $subscription->subscription_period,
                    'subscription_fee' => $subscription->subscription_fee,
                    'next_pay_date' => $calculatedDay,
                    'expired_date' => $calculatedDay,
                    'status' => 'Active',
                    'approval_date' => $subscription->expired_date,
                    'handle_by' => 1
                ]);

                $subscriber->update([
                    'subscription_id' => $newSubscription->id,
                ]);

                foreach ($subscription_batches as $batch) {
                    $batch->update([
                        'subscription_id' => $newSubscription->id,
                        'subscription_number' => $newSubscription->subscription_number,
                    ]);
                }

                $cash_wallet = Wallet::where('user_id', $user->id)->where('type', 'cash_wallet')->first();
                $cash_wallet->balance -= $newSubscription->subscription_fee;
                $cash_wallet->save();

                if ($newSubscription->subscription_fee > 0) {
                    Transaction::create([
                        'category' => 'trading_account',
                        'user_id' => $subscription->user_id,
                        'from_wallet_id' => $cash_wallet->id,
                        'transaction_number' => RunningNumberService::getID('transaction'),
                        'transaction_type' => 'SubscriptionFee',
                        'amount' => $newSubscription->subscription_fee,
                        'transaction_charges' => 0,
                        'transaction_amount' => $newSubscription->subscription_fee,
                        'status' => 'Success',
                    ]);
                }
            }

            SubscriptionsSchedulerLog::create([
                'old_subscription_id' => $subscription->id,
                'new_subscription_id' => $newSubscription != null ? $newSubscription->id : $subscription->id,
                'old_expired_date' => $subscription->expired_date,
                'new_expired_date' => $newSubscription != null ? $newSubscription->expired_date : $subscription->expired_date,
                'old_status' => $subscription->status,
                'new_status' => $newSubscription != null ? $newSubscription->status : $subscription->status,
            ]);
        }
    }
}
