<?php

namespace App\Services\Data;

use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTradingAccount
{
    public function execute(User $user, $data): TradingAccount
    {
        return $this->storeNewAccount($user, $data);
    }

    public function storeNewAccount(User $user, $data): TradingAccount
    {
        $tradingAccount = new TradingAccount();
        $tradingAccount->user_id = $user->id;
        $tradingAccount->meta_login = $data['login'];
        $tradingAccount->account_type = 1;
        $tradingAccount->margin_leverage = $data['leverage'];

        DB::transaction(function () use ($tradingAccount) {
            $tradingAccount->save();
        });

        return $tradingAccount;
    }
}
