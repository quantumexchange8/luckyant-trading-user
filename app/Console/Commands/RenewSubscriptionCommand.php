<?php

namespace App\Console\Commands;

use App\Models\Subscription;
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
        $subscriptions = Subscription::where('status', 'Active')->whereDate('expired_date', '2024-03-30')->get();
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        foreach ($subscriptions as $subscription) {
            $user = User::find($subscription->user_id);
            $subscription->update([
                'status' => 'Expired'
            ]);
            $meta_account = TradingAccount::find($subscription->trading_account_id);
            if ($connection == 0) {
                try {
                    $metaService->getUserInfo(collect([$meta_account]));
                } catch (\Exception $e) {
                    \Log::error('Error fetching trading accounts: '. $e->getMessage());
                }
            }

            $calculatedDay = now()->addDays($subscription->subscription_period + 1)->startOfDay();

            $newSubscription = Subscription::create([
                'user_id' => $subscription->user_id,
                'trading_account_id' => $subscription->trading_account_id,
                'meta_login' => $subscription->meta_login,
                'meta_balance' => $meta_account->balance,
                'transaction_id' => $subscription->transaction_id,
                'master_id' => $subscription->master_id,
                'subscription_number' => RunningNumberService::getID('subscription'),
                'subscription_period' => $subscription->subscription_period,
                'subscription_fee' => $subscription->subscription_fee,
                'next_pay_date' => $calculatedDay,
                'expired_date' => $calculatedDay,
                'status' => 'Active',
                'approval_date' => now()
            ]);

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
    }
}
