<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeLeverage;
use App\Models\AccountTypeToLeader;
use App\Models\PaymentAccount;
use App\Models\SettingLeverage;
use App\Models\Transaction;
use App\Models\Wallet;
use Auth;
use Illuminate\Http\Request;

class SelectOptionController extends Controller
{
    public function getLeverages(Request $request)
    {
        $type = $request->account_type;
        $account_type = AccountType::firstWhere('slug', $type);

        $settingLeverages = AccountTypeLeverage::with('leverage')
            ->where('account_type_id', $account_type->id)
            ->get()
            ->map(function ($settingLeverage) {
                return [
                    'label' => $settingLeverage->leverage->display,
                    'value' => $settingLeverage->leverage->value,
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
        } elseif ($request->account_type == 'standard_account' || $request->account_type == 'ecn_account') {
            $query->where('type', 'cash_wallet');
        }

        $wallets = $query->get();

        return response()->json($wallets);
    }

    public function getBalanceInAmount(Request $request)
    {
        $passBalanceIn = Transaction::where([
            'category' => 'trading_account',
            'transaction_type' => 'BalanceIn',
            'to_meta_login' => $request->meta_login,
            'status' => 'Success'
        ])
            ->first();

        if (empty($passBalanceIn)) {
            $minAmount = 100;
        } else {
            $minAmount = 10;
        }

        return response()->json($minAmount);
    }

    public function getWithdrawalWallets()
    {
        $wallets = Wallet::where('user_id', Auth::id())
            ->whereNot('type', 'e_wallet');

        return response()->json([
            'wallets' => $wallets->get(),
            'total_balance' => $wallets->sum('balance')
        ]);
    }

    public function getPaymentAccounts()
    {
        $paymentAccounts = PaymentAccount::where([
            'user_id' => Auth::id(),
            'status' => 'Active'
        ])
            ->latest()
            ->get();

        return response()->json($paymentAccounts);
    }
}
