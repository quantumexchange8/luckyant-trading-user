<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeLeverage;
use App\Models\AccountTypeToLeader;
use App\Models\Country;
use App\Models\Master;
use App\Models\MasterToLeader;
use App\Models\PammSubscription;
use App\Models\PaymentAccount;
use App\Models\SettingLeverage;
use App\Models\SettingRank;
use App\Models\SubscriptionBatch;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
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

    public function getCountries()
    {
        $countries = Country::select([
            'id',
            'name',
            'iso2',
            'phone_code',
            'nationality',
            'translations',
            'currency',
            'currency_symbol'
        ])
            ->get();

        return response()->json($countries);
    }

    public function getRanks()
    {
        $ranks = SettingRank::select([
            'id',
            'name'
        ])
            ->get();

        return response()->json($ranks);
    }

    public function getReferrers(Request $request)
    {
        $query = User::whereNotIn('role', [
            'super-admin',
            'admin',
        ])
            ->whereIn('id', Auth::user()->getChildrenIds())
            ->select([
                'id',
                'name',
                'username',
                'email'
            ]);

        $users = $query->get();

        return response()->json($users);
    }

    public function getMastersByType(Request $request)
    {
        $type = $request->type;
        $childrenIds = Auth::user()->getChildrenIds();

        if ($type == 'CopyTrade') {
            $master_ids = SubscriptionBatch::where([
                'type' => $type,
                'status' => 'Active'
            ])
                ->whereIn('user_id', $childrenIds)
                ->distinct('master_id')
                ->pluck('master_id');
        } else {
            $master_ids = PammSubscription::where([
                'type' => $type,
                'status' => 'Active'
            ])
                ->whereIn('user_id', $childrenIds)
                ->distinct('master_id')
                ->pluck('master_id');
        }

        $masters = Master::with('tradingUser')
            ->whereIn('id', $master_ids)
            ->get();

        return response()->json($masters);
    }

    public function getInternalTransferAccounts(Request $request)
    {
        $trading_accounts = TradingAccount::with([
            'subscription.master',
            'pamm_subscription.master'
        ])
            ->where('user_id', Auth::id())
            ->whereNot('meta_login', $request->meta_login)
            ->get()
            ->filter(function ($a) {
                if (!$a->subscription && !$a->pamm_subscription) {
                    return true;
                }

                if ($a->subscription) {
                    return $a->subscription->master?->can_top_up == 1;
                }

                if ($a->pamm_subscription) {
                    return $a->pamm_subscription->master?->can_top_up == 1;
                }

                return false;
            })
            ->values();

        return response()->json($trading_accounts);
    }
}
