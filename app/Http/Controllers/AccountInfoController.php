<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTradingAccountRequest;
use App\Models\AccountType;
use App\Models\TradingAccount;
use App\Services\MetaFiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountInfoController extends Controller
{
    public function index()
    {
        return Inertia::render('AccountInfo/AccountInfo');
    }

    public function add_trading_account(AddTradingAccountRequest $request)
    {
        $user = Auth::user();
        $group = AccountType::with('metaGroup')->where('id', 1)->get()->value('metaGroup.meta_group_name');
        $leverage = $request->leverage;

        $metaAccount = (new MetaFiveService())->createUser($user, $group, $leverage);

        return back()->with('toast', trans('public.Successfully Created Trading Account'));
    }

    public function refreshTradingAccountsData()
    {
        return $this->getTradingAccountsData();
    }

    protected function getTradingAccountsData()
    {
        $user = Auth::user();
        $connection = (new MetaFiveService())->getConnectionStatus();

        if ($connection == 0) {
            try {
                (new MetaFiveService())->getUserInfo($user->tradingAccounts);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }

        return TradingAccount::where('user_id', \Auth::id())->latest()->get();
    }

}
