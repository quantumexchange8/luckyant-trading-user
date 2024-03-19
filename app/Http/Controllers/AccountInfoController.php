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
use App\Notifications\AddTradingAccountNotification;
use App\Services\dealAction;
use App\Services\dealType;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
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

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $metaAccount = $metaService->createUser($user, $group, $leverage);

        Notification::route('mail', $user->email)
            ->notify(new AddTradingAccountNotification($metaAccount, $user));

        return back()->with('toast', trans('public.created_trading_account'));
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

        $tradingAccounts = TradingAccount::with(['tradingUser:id,name,meta_login', 'subscriber', 'masterRequest:id,trading_account_id,status'])
            ->where('user_id', \Auth::id())
            ->whereDoesntHave('masterAccount', function ($query) {
                $query->whereNotNull('trading_account_id');
            })
            ->latest()
            ->get();

        $tradingAccounts->each(function ($tradingAccount) {
            if ($tradingAccount->subscriber) {
                if ($tradingAccount->subscriber->unsubscribe_date < now()) {
                    $tradingAccount->balance_out = false;
                } else {
                    $tradingAccount->balance_out = true;
                }
            } else {
                $tradingAccount->balance_out = true;
            }
        });

        $masterAccounts = Master::with(['tradingAccount', 'tradingAccount.accountType:id,group_id,name', 'tradingUser:id,name,meta_login'])->where('user_id', \Auth::id())->get();

        return response()->json([
            'tradingAccounts' => $tradingAccounts,
            'totalEquity' => $tradingAccounts->sum('equity'),
            'totalBalance' => $tradingAccounts->sum('balance'),
            'masterAccounts' => $masterAccounts
        ]);
    }

    public function depositTradingAccount(DepositBalanceRequest $request)
    {
        $user = Auth::user();
        $wallet = Wallet::find($request->wallet_id);
        $amount = $request->amount;
        $meta_login = $request->to_meta_login;
        $cash_wallet = Wallet::where('type', 'cash_wallet')->where('user_id', $user->id)->first();

        if ($wallet->type == 'e_wallet') {
            $total_balance = $cash_wallet->balance + $wallet->balance;
            if ($total_balance < $amount || $amount <= 0) {
                throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
            }
        } elseif ($wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
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
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $new_wallet_amount = $wallet->balance - $amount;
        $transaction_amount = $amount;
        if ($wallet->type == 'e_wallet' && $new_wallet_amount < 0) {
            $remaining_amount = abs($new_wallet_amount);
            $transaction_amount = $wallet->balance;

            if ($transaction_amount > 0) {
                Transaction::create([
                    'category' => 'trading_account',
                    'user_id' => $user->id,
                    'from_wallet_id' => $wallet->id,
                    'to_meta_login' => $meta_login,
                    'ticket' => $deal['deal_Id'],
                    'transaction_number' => RunningNumberService::getID('transaction'),
                    'transaction_type' => 'Deposit',
                    'amount' => $transaction_amount,
                    'transaction_charges' => 0,
                    'transaction_amount' => $transaction_amount,
                    'status' => 'Success',
                    'comment' => $deal['conduct_Deal']['comment'],
                    'new_wallet_amount' => 0,
                ]);
            }

            $new_wallet_amount = 0;

            $cash_wallet->balance -= $remaining_amount;
            $cash_wallet->save();

            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'from_wallet_id' => $cash_wallet->id,
                'to_meta_login' => $meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'Deposit',
                'amount' => $remaining_amount,
                'transaction_charges' => 0,
                'transaction_amount' => $remaining_amount,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
                'new_wallet_amount' => $cash_wallet->balance,
            ]);
        } else {
            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
                'to_meta_login' => $meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'Deposit',
                'amount' => $transaction_amount,
                'transaction_charges' => 0,
                'transaction_amount' => $transaction_amount,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
                'new_wallet_amount' => $new_wallet_amount,
            ]);
        }

        $wallet->update(['balance' => $new_wallet_amount]);

        return redirect()->back()
            ->with('title', trans('public.success_deposit'))
            ->with('success', trans('public.successfully_deposit') . ' $' . number_format($amount, 2) . trans('public.to_login') . ': ' . $request->to_meta_login);
    }

    public function withdrawTradingAccount(WithdrawBalanceRequest $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $user = Auth::user();
        $wallet = Wallet::find($request->to_wallet_id);
        $amount = $request->amount;
        $tradingAccount = TradingAccount::with('subscriber')->where('meta_login', $request->from_meta_login)->first();

        try {
            $metaService->getUserInfo(collect([$tradingAccount]));
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        // Check if balance is sufficient
        if (!empty($tradingAccount->subscriber) && $tradingAccount->subscriber->unsubscribe_date < now()) {
            throw ValidationException::withMessages(['amount' => trans('public.terminatiion_message')]);
        }

        // Check if balance is sufficient
        if ($tradingAccount->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
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
            ->with('title', trans('public.success_withdraw'))
            ->with('success', trans('public.successfully_withdraw') . ' $' . number_format($amount, 2) . trans('public.from_login') . ': ' . $request->from_meta_login);
    }

    public function internalTransferTradingAccount(InternalTransferBalanceRequest $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
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
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
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
            ->with('title', trans('public.success_internal_transaction'))
            ->with('success', trans('public.successfully_transfer') . ' $' . number_format($amount, 2) . trans('public.from_login') . ': ' . $request->from_meta_login . ' ' . trans('public.to_login') . ': ' . $request->to_meta_login);
    }

    public function getTradingAccounts(Request $request)
    {
        if ($request->type == 'internal_transfer') {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())->whereNot('meta_login', $request->meta_login)->get();
        } elseif ($request->type == 'subscribe') {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())
                ->where(function ($query) {
                    $query->whereDoesntHave('masterAccount', function ($subQuery) {
                        $subQuery->whereNotNull('trading_account_id');
                    })
                        ->whereDoesntHave('subscriber', function ($subQuery) {
                            $subQuery->whereNotNull('trading_account_id');
                        });
                })
                ->orWhere(function ($query) {
                    $query->whereHas('subscriber', function ($subQuery) {
                        $subQuery->where('user_id', Auth::id())
                            ->whereNotIn('status', ['Pending', 'Subscribing']);
                    });
                })
                ->whereNot('meta_login', $request->meta_login)
                ->get();
        } else {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())->get();
        }

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
                'label' => $tradingAccount->meta_login . ' ($' . number_format($tradingAccount->equity, 2) . ')',
            ];
        });
    }

    public function becomeMaster(Request $request)
    {
        $trading_account = TradingAccount::where('meta_login', $request->meta_login)->first();

        MasterRequest::create([
            'user_id' =>  Auth::id(),
            'trading_account_id' =>  $trading_account->id,
            'min_join_equity' => $request->min_join_equity,
            'roi_period' => $request->roi_period,
            'sharing_profit' => $request->sharing_profit,
            'subscription_fee' => $request->subscription_fee,
        ]);

        return redirect()->back()
            ->with('title', trans('public.success_submission'))
            ->with('success', trans('public.successfully_submission') . ': ' . $request->meta_login);
    }

    public function master_profile(Request $request, $meta_login)
    {
        $masterAccount = Master::with('tradingAccount.accountType:id,group_id,name')->where('meta_login', $meta_login)->first();

       return Inertia::render('AccountInfo/MasterAccount/MasterProfile', [
           'masterAccount' => $masterAccount,
           'subscriberCount' => $masterAccount->subscribers->count(),
       ]);
    }

    public function updateMasterConfiguration(MasterConfigurationRequest $request)
    {
        $master = Master::find($request->master_id);

        $master->update([
            'min_join_equity' => $request->min_join_equity,
            'sharing_profit' => $request->sharing_profit,
            'subscription_fee' => $request->subscription_fee,
            'roi_period' => $request->roi_period,
            'signal_status' => $request->signal_status,
        ]);

        if ($master->min_join_equity != null &&
            $master->sharing_profit != null &&
            $master->roi_period != null &&
            $master->subscription_fee != null) {
            $master->update([
                'status' => 'Active',
            ]);
        }

        return redirect()->back()
            ->with('title', trans('public.success_configure_setting'))
            ->with('success', trans('public.successfully_configure_setting') . ': ' . $master->meta_login);
    }

    public function getRequestHistory(Request $request)
    {
        $user = Auth::user();

        $masterRequest = MasterRequest::with(['trading_account:id,meta_login'])
            ->where('user_id', $user->id)
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->whereHas('trading_account', function ($q) use ($search) {
                    $q->where('meta_login', 'like', $search);
                });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $query->where('status', $type);
            })
            ->latest()
            ->paginate(10);

        return response()->json($masterRequest);
    }
}
