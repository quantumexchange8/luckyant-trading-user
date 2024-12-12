<?php

namespace App\Services\Data;

use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTradingUser
{
    public function execute(User $user, $data, $group): TradingUser
    {
        return $this->storeNewUser($user, $data, $group);
    }

    public function storeNewUser(User $user, $data, $group): TradingUser
    {
        $accountType = AccountType::firstWhere('name', $group);

        $tradingUser = new TradingUser();
        $tradingUser->user_id = $user->id;
        $tradingUser->name = $data['name'];
        $tradingUser->meta_login = $data['login'];
        $tradingUser->meta_group = $accountType->name;
        $tradingUser->account_type = $accountType->id;
        $tradingUser->leverage = $data['leverage'];

        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });

        return $tradingUser;
    }
}
