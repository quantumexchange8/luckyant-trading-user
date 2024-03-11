<?php

namespace App\Services;

use App\Models\PaymentAccount;
use App\Models\Wallet;

class SelectOptionService
{
    public function getWalletSelection(): \Illuminate\Support\Collection
    {
        $wallets = Wallet::where('user_id', \Auth::id())->whereIn('type', ['cash_wallet', 'e_wallet']);

        return $wallets->get()->map(function ($wallet) {
            return [
                'value' => $wallet->id,
                'label' => $wallet->name . ' ($' . number_format($wallet->balance, 2) . ')',
                'balance' => $wallet->balance,
            ];
        });
    }

    public function getPaymentAccountSelection(): \Illuminate\Support\Collection
    {
        $paymentAccounts = PaymentAccount::where('user_id', \Auth::id())->where('status', 'Active')->latest();

        return $paymentAccounts->get()->map(function ($payment_account) {
            return [
                'value' => $payment_account->id,
                'label' => $payment_account->payment_platform_name . ' (' . $payment_account->payment_account_name . ')',
            ];
        });
    }
}
