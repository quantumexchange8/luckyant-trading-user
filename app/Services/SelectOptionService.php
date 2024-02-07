<?php

namespace App\Services;

use App\Models\Wallet;

class SelectOptionService
{
    public function getWalletSelection(): \Illuminate\Support\Collection
    {
        $wallets = Wallet::where('user_id', \Auth::id());

        return $wallets->get()->map(function ($wallet) {
            return [
                'value' => $wallet->id,
                'label' => $wallet->name . ' ($' . number_format($wallet->balance, 2) . ')',
            ];
        });
    }
}
