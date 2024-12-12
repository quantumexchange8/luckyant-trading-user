<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeToLeader;
use App\Models\SettingLeverage;
use App\Models\Wallet;
use Auth;

class SelectOptionController extends Controller
{
    public function getLeverages()
    {
        $settingLeverages = SettingLeverage::where('status', 'Active')
            ->get()->map(function ($settingLeverage) {
                return [
                    'label' => $settingLeverage->display,
                    'value' => $settingLeverage->value,
                ];
        });

        return response()->json($settingLeverages);
    }

    public function getAccountTypes()
    {
        $leader = Auth::user()->getFirstLeader();

        $account_type_ids = AccountTypeToLeader::where('user_id', $leader->id)
            ->pluck('account_type_id')
            ->toArray();

        $accountTypes = AccountType::select([
            'id',
            'name',
            'slug'
        ])
            ->whereIn('id', $account_type_ids)
            ->get();

        return response()->json($accountTypes);
    }

    public function getDepositWallets()
    {
        $wallets = Wallet::where('user_id', Auth::id())
            ->whereNot('type', 'bonus_wallet')
            ->get();

        return response()->json($wallets);
    }
}
