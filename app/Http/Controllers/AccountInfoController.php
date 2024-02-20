<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTradingAccountRequest;
use App\Http\Requests\DepositBalanceRequest;
use App\Http\Requests\InternalTransferBalanceRequest;
use App\Http\Requests\MasterConfigurationRequest;
use App\Http\Requests\WithdrawBalanceRequest;
use App\Models\AccountType;
use App\Models\Master;
use App\Models\MasterRequest;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\dealAction;
use App\Services\dealType;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountInfoController extends Controller
{
    public function index()
    {
        return Inertia::render('AccountInfo/AccountInfo', [
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'accountCounts' => Auth::user()->tradingAccounts->count(),
            'masterAccountLogin' => Master::where('user_id', Auth::id())->pluck('meta_login')->toArray()
        ]);
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

        $tradingAccounts = TradingAccount::with('accountType:id,group_id,name')
            ->where('user_id', \Auth::id())
            ->whereDoesntHave('masterAccount', function ($query) {
                $query->whereNotNull('trading_account_id');
            })
            ->latest()
            ->get();

        $masterAccounts = Master::with(['tradingAccount', 'tradingAccount.accountType:id,group_id,name'])->where('user_id', \Auth::id())->get();

        return response()->json([
            'tradingAccounts' => $tradingAccounts,
            'masterAccounts' => $masterAccounts
        ]);
    }

    public function depositTradingAccount(DepositBalanceRequest $request)
    {
        $user = Auth::user();
        $wallet = Wallet::find($request->wallet_id);
        $amount = $request->amount;
        $meta_login = $request->to_meta_login;

        if ($wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.Insufficient balance')]);
        }

        $deal = [];

        $connection = (new MetaFiveService())->getConnectionStatus();
        if ($connection == 0) {
            try {
                $deal = (new MetaFiveService())->createDeal($meta_login, $amount, 'Deposit to trading account', dealAction::DEPOSIT);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        } else {
            return redirect()->back()
                ->with('title', 'Server under maintenance')
                ->with('warning', 'Please try again later');
        }

        $new_wallet_amount = $wallet->balance - $amount;
        $transaction_number = RunningNumberService::getID('transaction');

        Transaction::create([
            'category' => 'trading_account',
            'user_id' => $user->id,
            'from_wallet_id' => $wallet->id,
            'to_meta_login' => $meta_login,
            'ticket' => $deal['deal_Id'],
            'transaction_number' => $transaction_number,
            'transaction_type' => 'Deposit',
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Success',
            'comment' => $deal['conduct_Deal']['comment'],
            'new_wallet_amount' => $new_wallet_amount,
        ]);

        $wallet->update([
           'balance' => $new_wallet_amount
        ]);

        return redirect()->back()
            ->with('title', 'Success deposit')
            ->with('success', 'Successfully deposit $' . number_format($amount, 2) . ' to LOGIN: ' . $request->to_meta_login);
    }

    public function withdrawTradingAccount(WithdrawBalanceRequest $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', 'Server under maintenance')
                ->with('warning', 'Please try again later');
        }

        $user = Auth::user();
        $wallet = Wallet::find($request->to_wallet_id);
        $amount = $request->amount;
        $tradingAccount = TradingAccount::where('meta_login', $request->from_meta_login)->first();

        try {
            $metaService->getUserInfo(collect([$tradingAccount]));
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        // Check if balance is sufficient
        if ($tradingAccount->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.Insufficient balance')]);
        }
        $deal = [];
        try {
            $deal = $metaService->createDeal($tradingAccount->meta_login, $amount, 'Withdraw from trading account', dealAction::WITHDRAW);
        } catch (\Exception $e) {
            \Log::error('Error creating deal: '. $e->getMessage());
        }

        // Calculate new wallet amount
        $new_wallet_amount = $wallet->balance + $amount;
        $transaction_number = RunningNumberService::getID('transaction');

        // Create transaction
        Transaction::create([
            'category' => 'trading_account',
            'user_id' => $user->id,
            'to_wallet_id' => $wallet->id,
            'from_meta_login' => $tradingAccount->meta_login,
            'ticket' => $deal['deal_Id'],
            'transaction_number' => $transaction_number,
            'transaction_type' => 'Withdrawal',
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Success',
            'comment' => $deal['conduct_Deal']['comment'],
            'new_wallet_amount' => $new_wallet_amount,
        ]);

        $wallet->update([
            'balance' => $new_wallet_amount
        ]);

        return redirect()->back()
            ->with('title', 'Success withdraw')
            ->with('success', 'Successfully withdraw $' . number_format($amount, 2) . ' from LOGIN: ' . $request->from_meta_login);
    }

    public function internalTransferTradingAccount(InternalTransferBalanceRequest $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', 'Server under maintenance')
                ->with('warning', 'Please try again later');
        }

        $user = Auth::user();
        $from_trading_account = TradingAccount::where('meta_login', $request->from_meta_login)->first();
        $to_trading_account = TradingAccount::where('meta_login', $request->to_meta_login)->first();
        $amount = $request->amount;

        try {
            $metaService->getUserInfo(collect([$from_trading_account]));
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        // Check if balance is sufficient
        if ($from_trading_account->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.Insufficient balance')]);
        }

        $deal_1 = [];
        $deal_2 = [];
        try {
            $deal_1 = $metaService->createDeal($from_trading_account->meta_login, $amount, "Trading Account To Trading Account", dealAction::WITHDRAW);
        } catch (\Throwable $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }
        try {
            $deal_2 = $metaService->createDeal($to_trading_account->meta_login, $amount, "Trading Account To Trading Account", dealAction::DEPOSIT);
        } catch (\Throwable $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }
        $ticket = $deal_1['deal_Id'] . ', ' . $deal_2['deal_Id'];

        $transaction_number = RunningNumberService::getID('transaction');

        // Create transaction
        Transaction::create([
            'category' => 'trading_account',
            'user_id' => $user->id,
            'from_meta_login' => $from_trading_account->meta_login,
            'to_meta_login' => $to_trading_account->meta_login,
            'ticket' => $ticket,
            'transaction_number' => $transaction_number,
            'transaction_type' => 'InternalTransfer',
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Success',
            'comment' => $deal_1['conduct_Deal']['comment'],
        ]);

        return redirect()->back()
            ->with('title', 'Success internal transaction')
            ->with('success', 'Successfully transfer $' . number_format($amount, 2) . ' from LOGIN: ' . $request->from_meta_login . ' to LOGIN: ' . $request->to_meta_login);
    }

    public function getTradingAccounts(Request $request)
    {
        $tradingAccount = TradingAccount::where('user_id', Auth::id())->whereNot('meta_login', $request->meta_login)->get();

        $connection = (new MetaFiveService())->getConnectionStatus();
        if ($connection == 0) {
            try {
                (new MetaFiveService())->getUserInfo($tradingAccount);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }

        return $tradingAccount->map(function ($tradingAccount) {
            return [
                'value' => $tradingAccount->meta_login,
                'label' => $tradingAccount->meta_login . ' ($' . number_format($tradingAccount->balance, 2) . ')',
            ];
        });
    }

    public function becomeMaster(Request $request)
    {
        $trading_account = TradingAccount::where('meta_login', $request->meta_login)->first();

        MasterRequest::create([
            'user_id' =>  Auth::id(),
            'trading_account_id' =>  $trading_account->id,
        ]);

        return redirect()->back()
            ->with('title', 'Success submission')
            ->with('success', 'Successfully submit request to become Master Account for LOGIN: ' . $request->meta_login);
    }

    public function master_configuration(Request $request, $meta_login)
    {
        $masterAccount = Master::with('tradingAccount.accountType:id,group_id,name')->where('meta_login', $meta_login)->first();

       return Inertia::render('AccountInfo/MasterAccount/MasterConfiguration', [
           'masterAccount' => $masterAccount
       ]);
    }

    public function updateMasterConfiguration(MasterConfigurationRequest $request)
    {
        $master = Master::find($request->master_id);

        $master->update([
            'min_join_equity' => $request->min_join_equity,
            'sharing_profit' => $request->sharing_profit,
            'subscription_fee' => $request->subscription_fee,
            'signal_status' => $request->signal_status,
        ]);

        if ($master->min_join_equity != null &&
            $master->sharing_profit != null &&
            $master->subscription_fee != null) {
            $master->update([
                'status' => 'Active',
            ]);
        }

        return redirect()->back()
            ->with('title', 'Success configure setting')
            ->with('success', 'Successfully configure requirements to follow Master Account for LOGIN: ' . $master->meta_login);
    }
}
