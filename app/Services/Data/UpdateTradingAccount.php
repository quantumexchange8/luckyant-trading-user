<?php

namespace App\Services\Data;

use App\Models\AccountType;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTradingAccount
{
    public function execute($meta_login, $data): TradingAccount
    {
        return $this->updateTradingAccount($meta_login, $data);
    }

    public function updateTradingAccount($meta_login, $data): TradingAccount
    {
        $tradingAccount = TradingAccount::with('accountType')->firstWhere('meta_login', $meta_login);

        $tradingUser = $tradingAccount->tradingUser;

        if ($tradingUser->acc_status === "Active" && $tradingAccount->accountType->slug != 'virtual') {
            $tradingAccount->currency_digits = $data['currencyDigits'];
            $tradingAccount->balance = $data['balance'];
            $tradingAccount->credit = $data['credit'];
            $tradingAccount->margin_leverage = $data['marginLeverage'];
            $tradingAccount->equity = $data['equity'];
            $tradingAccount->floating = $data['floating'];
            $tradingAccount->account_type = $tradingUser->account_type;
            DB::transaction(function () use ($tradingAccount) {
                $tradingAccount->save();
            });
        }

        return $tradingAccount;
    }
}
