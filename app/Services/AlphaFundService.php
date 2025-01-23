<?php

namespace App\Services;

use App\Models\AccountType;
use App\Models\PammSubscription;
use App\Models\SubscriptionBatch;
use App\Models\Transaction;
use Carbon\Carbon;

class AlphaFundService
{
    public static function calculateRemainingQuota($user): array
    {
        // Calculate total bonus up to the last month
        $totalBonus = Transaction::where([
            'user_id' => $user->id,
            'status' => 'Success',
        ])
            ->whereIn('transaction_type', ['SameLevelRewards', 'ProfitSharingPayout', 'ProfitSharing', 'PerformanceIncentive', 'LotSizeRebate', 'ExtraBonus'])
            ->whereDate('created_at', '<=', Carbon::now()->startOfMonth()->subDay())
            ->sum('amount');

        // Calculate active funds up to the last month
        $activeCopyTradeFund = SubscriptionBatch::where([
            'user_id' => $user->id,
            'status' => 'Active',
        ])
            ->whereDate('created_at', '<=', Carbon::now()->startOfMonth()->subDay())
            ->sum('meta_balance');

        $activePammFund = PammSubscription::where([
            'user_id' => $user->id,
            'status' => 'Active',
        ])
            ->whereDate('created_at', '<=', Carbon::now()->startOfMonth()->subDay())
            ->sum('subscription_amount');

        $totalActiveFund = $activeCopyTradeFund + $activePammFund;

        // Calculate max deposit percentage for "alpha"
        $alpha_max_percentage = AccountType::firstWhere('slug', 'alpha')->max_deposit_percentage;

        // Calculate available deposit balance
        $availableDepositBalance = $totalActiveFund * ($alpha_max_percentage / 100);

        // Determine the current month's max deposit amount
        $currentMonthMaxDeposit = min($availableDepositBalance, $totalBonus);

        // Calculate total of previous BalanceIn transactions
        $totalBalanceInUsed = Transaction::where([
            'user_id' => $user->id,
            'status' => 'Success',
            'transaction_type' => 'BalanceIn',
        ])
            ->whereHas('to_meta_login', function ($query) use ($user) {
                $query->where([
                    'user_id' => $user->id,
                ])->whereHas('accountType', function ($q) {
                    $q->where('slug', 'alpha');
                });
            })
            ->sum('amount');

        // Calculate the remaining quota for total bonus
        $remainingQuota = $totalBonus - $totalBalanceInUsed;

        // Adjust the remainingQuota to ensure it respects the current month's max deposit amount
        if (($totalBalanceInUsed + $remainingQuota) > $currentMonthMaxDeposit) {
            $remainingQuota = $currentMonthMaxDeposit - $totalBalanceInUsed;
        }

        // Ensure remainingQuota is not negative
        $remainingQuota = max(0, $remainingQuota);

        return [
            'remaining_quota' => $remainingQuota,
            'available_deposit_balance' => $availableDepositBalance,
            'active_copy_trade_fund' => $activeCopyTradeFund,
            'active_pamm_fund' => $activePammFund,
            'total_active_fund' => $totalActiveFund,
            'current_month_max_deposit' => $currentMonthMaxDeposit,
            'total_bonus' => $totalBonus,
            'total_balance_in_used' => $totalBalanceInUsed,
        ];
    }
}
