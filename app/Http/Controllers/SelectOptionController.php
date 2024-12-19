<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeToLeader;
use App\Models\SettingLeverage;
use App\Models\Wallet;
use Auth;
use Illuminate\Http\Request;

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

    public function getDepositWallets(Request $request)
    {
        $query = Wallet::where('user_id', Auth::id());

        if ($request->account_type == 'alpha') {
            $query->whereNot('type', 'e_wallet');
        } else {
            $query->whereNot('type', 'bonus_wallet');
        }

        $wallets = $query->get();

        return response()->json($wallets);
    }
}
