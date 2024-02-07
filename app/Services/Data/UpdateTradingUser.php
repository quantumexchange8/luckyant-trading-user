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

        $tradingUser->leverage = $data['marginLeverage'];
        $tradingUser->balance = $data['balance'];
        $tradingUser->credit = $data['credit'];
        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });


        return $tradingUser;
    }
}
