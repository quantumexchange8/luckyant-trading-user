<?php

namespace App\Services\Data;

use App\Models\TradingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTradingUser
{
    public function execute($meta_login, $data): TradingUser
    {
        return $this->updateTradingUser($meta_login, $data);
    }

    public function updateTradingUser($meta_login, $data): TradingUser
    {
        $tradingUser = TradingUser::query()->where('meta_login', $meta_login)->first();

        if ($tradingUser->acc_status === "Active" && $tradingUser->account_type === 1){
            $tradingUser->name = $data['name'];
            $tradingUser->company = $data['company'];
            $tradingUser->leverage = $data['leverage'];
            $tradingUser->balance = $data['balance'];
            $tradingUser->credit = $data['credit'];
            $tradingUser->rights = $data['rights'];

            if ($data['rights'] == 5) {
                $tradingUser->allow_trade = false;
            } elseif ($data['rights'] == 1) {
                $tradingUser->allow_trade = true;
            }

            DB::transaction(function () use ($tradingUser) {
                $tradingUser->save();
            });
        }

        return $tradingUser;
    }
}
