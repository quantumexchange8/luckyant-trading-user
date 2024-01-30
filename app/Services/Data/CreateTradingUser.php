<?php

namespace App\Services\Data;

use App\Models\TradingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTradingUser
{
    public function execute(User $user, $data): TradingUser
    {
        return $this->storeNewUser($user, $data);
    }

    public function storeNewUser(User $user, $data): TradingUser
    {
        $tradingUser = new TradingUser();
        $tradingUser->user_id = $user->id;
        $tradingUser->meta_login = $data['login'];
        $tradingUser->meta_group = 'JS';
        $tradingUser->account_type = 1;
        $tradingUser->main_password = Hash::make($data['mainPassword']);
        $tradingUser->invest_password = Hash::make($data['investPassword']);
        $tradingUser->leverage = $data['leverage'];

        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });

        return $tradingUser;
    }
}
