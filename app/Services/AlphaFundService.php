<?php

namespace App\Services;

use App\Models\PammSubscription;
use App\Models\SubscriptionBatch;
use App\Models\Transaction;

class AlphaFundService
{
    public static function calculateRemainingQuota($user): array
    {
        // Calculate total bonus
        $totalBonus = Transaction::where([
            'user_id' => $user->id,
            'status' => 'Success',
        ])
            ->whereIn('transaction_type', ['SameLevelRewards', 'ProfitSharingPayout', 'ProfitSharing', 'PerformanceIncentive', 'LotSizeRebate'])
            ->sum('amount');

        // Calculate active funds
        $activeCopyTradeFund = SubscriptionBatch::where([
            'user_id' => $user->id,
            'status' => 'Active',
        ])->sum('meta_balance');

        $activePammFund = PammSubscription::where([
            'user_id' => $user->id,
            'status' => 'Active',
        ])->sum('subscription_amount');

        // Calculate available deposit balance
        $availableDepositBalance = ($activeCopyTradeFund + $activePammFund) * 0.2;

        // Calculate total of previous BalanceIn transactions
        $totalBalanceInUsed = Transaction::where([
            'user_id' => $user->id,
            'status' => 'Success',
            'transaction_type' => 'BalanceIn',
        ])
            ->whereHas('to_meta_login', function ($query) use ($user) {
                $query->where([
                    'user_id' => $user->id,
                    'account_type' => 3,
                ]);
            })
            ->sum('amount');

        // Calculate the remaining quota for total bonus
        $remainingQuota = $totalBonus - $totalBalanceInUsed;

        // Adjust the remainingQuota to ensure it respects the availableDepositBalance
        if (($totalBalanceInUsed + $remainingQuota) > $availableDepositBalance) {
            $remainingQuota = $availableDepositBalance - $totalBalanceInUsed;
        }

        // Ensure remainingQuota is not negative
        $remainingQuota = max(0, $remainingQuota);

        return [
            'remaining_quota' => $remainingQuota,
            'available_deposit_balance' => $availableDepositBalance,
            'active_copy_trade_fund' => $activeCopyTradeFund,
            'active_pamm_fund' => $activePammFund,
            'total_bonus' => $totalBonus,
            'total_balance_in_used' => $totalBalanceInUsed,
        ];
    }
}
