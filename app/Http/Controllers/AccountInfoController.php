<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\SubscriptionBatch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Master;
use App\Models\Wallet;
use App\Models\Setting;
use App\Services\dealType;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Services\dealAction;
use Illuminate\Http\Request;
use App\Models\MasterRequest;
use App\Models\TradingAccount;
use App\Services\passwordType;
use Illuminate\Support\Carbon;
use App\Services\MetaFiveService;
use App\Models\CopyTradeTransaction;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectOptionService;
use App\Services\RunningNumberService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Http\Requests\DepositBalanceRequest;
use Illuminate\Support\Facades\Notification;
use App\Http\Requests\WithdrawBalanceRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\AddTradingAccountRequest;
use App\Http\Requests\MasterConfigurationRequest;
use App\Notifications\AddTradingAccountNotification;
use App\Http\Requests\InternalTransferBalanceRequest;
use App\Notifications\ChangeTradingAccountPassowrdNotification;

class AccountInfoController extends Controller
{
    public function index()
    {
        $tradingAccounts = TradingAccount::with(['tradingUser:id,user_id,name,meta_login,company', 'subscriber', 'masterRequest:id,trading_account_id,status', 'ofUser:id,username'])
        ->where('user_id', \Auth::id())
        ->whereDoesntHave('masterAccount', function ($query) {
            $query->whereNotNull('trading_account_id');
        })
        ->latest()
        ->get();

        return Inertia::render('AccountInfo/AccountInfo', [
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'leverageSel' => (new SelectOptionService())->getActiveLeverageSelection(),
            'accountCounts' => Auth::user()->tradingAccounts->count(),
            'masterAccountLogin' => Master::where('user_id', Auth::id())->pluck('meta_login')->toArray(),
            'liveAccountQuota' => Setting::where('slug', 'live_account_quota')->first(),
            'totalEquity' => $tradingAccounts->sum('equity'),
            'totalBalance' => $tradingAccounts->sum('balance'),
        ]);
    }

    public function add_trading_account(AddTradingAccountRequest $request)
    {
        $user = Auth::user();

        $liveAccountQuota = Setting::where('slug', 'live_account_quota')->first()->value;

        if ($user->tradingAccounts->count() >= $liveAccountQuota) {
            return redirect()->back()
                ->with('title', trans('public.live_account_quota'))
                ->with('warning', trans('public.live_account_quota_warning'));
        }

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
        $balance = TradingAccount::where('meta_login', $metaAccount['login'])->value('balance');

        Notification::route('mail', $user->email)
            ->notify(new AddTradingAccountNotification($metaAccount, $balance, $user));

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

        $tradingAccounts = TradingAccount::with(['tradingUser:id,user_id,name,meta_login,company', 'masterRequest:id,trading_account_id,status'])
            ->where('user_id', \Auth::id())
            ->whereDoesntHave('masterAccount', function ($query) {
                $query->whereNotNull('trading_account_id');
            })
            ->latest()
            ->get();

        $tradingAccounts->each(function ($tradingAccount) {
            $activeSubscriber = Subscriber::with(['master', 'master.tradingUser'])
                ->where('meta_login', $tradingAccount->meta_login)
                ->where('status', 'Subscribing')
                ->first();

            if ($activeSubscriber && \Carbon\Carbon::parse($activeSubscriber->unsubscribe_date)->greaterThan(\Carbon\Carbon::now()->subHours(24))) {
                $tradingAccount->balance_out = false;
            } elseif ($activeSubscriber) {
                $tradingAccount->balance_out = false;
            } elseif ($tradingAccount->demo_fund > 0) {
                $tradingAccount->balance_out = false;
            } else {
                $tradingAccount->balance_out = true;
            }

            $tradingAccount->active_subscriber = $activeSubscriber;
        });

        $masterAccounts = Master::with(['tradingAccount', 'tradingAccount.accountType:id,group_id,name', 'tradingUser:id,user_id,name,meta_login,company'])->where('user_id', \Auth::id())->get();

        $masterAccounts->each(function ($masterAccount) {
            if ($masterAccount->tradingAccount->demo_fund > 0) {
                $masterAccount->tradingAccount->balance_out = false;
            } else {
                $masterAccount->tradingAccount->balance_out = true;
            }
        });

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

        $minEWalletAmount = $request->minEWalletAmount;
        $maxEWalletAmount = $request->maxEWalletAmount;
        $eWalletAmount = $request->eWalletAmount;
        $cashWalletAmount = $request->cashWalletAmount;

        // amount validations
        if ($eWalletAmount < $minEWalletAmount) {
            throw ValidationException::withMessages(['eWalletAmount' => trans('public.min_e_wallet_error')]);
        } elseif ($eWalletAmount > $maxEWalletAmount) {
            throw ValidationException::withMessages(['eWalletAmount' => trans('public.max_e_wallet_error')]);
        }

        if (($eWalletAmount + $cashWalletAmount) !== $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.e_wallet_amount_error', ['SumAmount' => $eWalletAmount + $cashWalletAmount, 'DepositAmount' => $amount])]);
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $eWalletAmount)) {
            throw ValidationException::withMessages(['eWalletAmount' => trans('public.invalid_e_wallet_amount')]);
        }

        if ($wallet->type == 'e_wallet') {
            if ($wallet->balance < $eWalletAmount || $amount <= 0) {
                throw ValidationException::withMessages(['amount' => trans('public.insufficient_wallet_balance', ['wallet' => trans('public.' . $wallet->type)])]);
            }
            if ($cash_wallet->balance < $cashWalletAmount) {
                throw ValidationException::withMessages(['amount' => trans('public.insufficient_wallet_balance', ['wallet' => trans('public.' . $cash_wallet->type)])]);
            }
        } elseif ($wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_wallet_balance', ['wallet' => trans('public.' . $wallet->type)])]);
        }

        // conduct deal and transaction record
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

        if ($wallet->type == 'e_wallet') {
            $this->createTransaction($user->id, $wallet->id, $meta_login, $deal['deal_Id'], 'BalanceIn', $eWalletAmount, $wallet->balance - $eWalletAmount, $deal['conduct_Deal']['comment'], 0, 'trading_account');
            $wallet->balance -= $eWalletAmount;
            $wallet->save();

            $this->createTransaction($user->id, $cash_wallet->id, $meta_login, $deal['deal_Id'], 'BalanceIn', $cashWalletAmount, $cash_wallet->balance - $cashWalletAmount, $deal['conduct_Deal']['comment'], 0, 'trading_account');
            $cash_wallet->balance -= $cashWalletAmount;
            $cash_wallet->save();
        } else {
            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
                'to_meta_login' => $meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'BalanceIn',
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
                'new_wallet_amount' => $wallet->balance - $amount,
            ]);

            $wallet->update(['balance' => $wallet->balance - $amount]);
        }

        // check subscriber
        $subscriber = Subscriber::with(['master:id,meta_login', 'tradingAccount'])
            ->where('user_id', $user->id)
            ->where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Subscribing'])
            ->first();

        if ($subscriber && $subscriber->status == 'Pending') {
            $subscriber->initial_meta_balance += $amount;
            $subscriber->save();
        } elseif ($subscriber && $subscriber->status == 'Subscribing') {
            $subscriber->subscribe_amount += $amount;
            $subscriber->save();

            $subscriptions = Subscription::with(['master:id,meta_login', 'tradingAccount'])
                ->where('user_id', $user->id)
                ->where('meta_login', $meta_login)
                ->whereIn('status', ['Pending', 'Active'])
                ->get();

            if ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $subscription->meta_balance += $amount;
                    $subscription->save();
                    if ($subscription->status == 'Active') {
                        CopyTradeTransaction::create([
                            'user_id' => $user->id,
                            'trading_account_id' => $subscription->tradingAccount->id,
                            'meta_login' => $subscription->tradingAccount->meta_login,
                            'subscription_id' => $subscription->id,
                            'master_id' => $subscription->master->id,
                            'master_meta_login' => $subscription->master->meta_login,
                            'amount' => $amount,
                            'real_fund' => $amount,
                            'demo_fund' => 0,
                            'type' => 'Deposit',
                            'status' => 'Success',
                        ]);

                        SubscriptionBatch::create([
                            'user_id' => $user->id,
                            'trading_account_id' => $subscriber->trading_account_id,
                            'meta_login' => $meta_login,
                            'meta_balance' => $amount,
                            'real_fund' => $amount,
                            'demo_fund' => 0,
                            'master_id' => $subscriber->master_id,
                            'master_meta_login' => $subscriber->master_meta_login,
                            'type' => 'CopyTrade',
                            'subscriber_id' => $subscriber->id,
                            'subscription_id' => $subscription->id,
                            'subscription_number' => $subscription->subscription_number,
                            'subscription_period' => $subscriber->roi_period,
                            'transaction_id' => $subscriber->transaction_id,
                            'subscription_fee' => $subscriber->initial_subscription_fee,
                            'settlement_start_date' => now(),
                            'settlement_date' => now()->addDays($subscriber->roi_period)->endOfDay(),
                            'status' => 'Active',
                            'approval_date' => now(),
                        ]);
                    }
                }
            }
        }

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
        $meta_login = $request->from_meta_login;
        $tradingAccount = TradingAccount::where('meta_login', $meta_login)->firstOrFail();

        try {
            $metaService->getUserInfo(collect([$tradingAccount]));
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: ' . $e->getMessage());
        }

        if ($tradingAccount->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        $subscriberHistory = Subscriber::where('meta_login', $meta_login)
            ->whereNotIn('status', ['Pending', 'Rejected'])
            ->get();

        if ($subscriberHistory->isNotEmpty()) {
            $lastUnsubscribed = $subscriberHistory->where('status', 'Unsubscribed')
                ->sortByDesc('unsubscribe_date')
                ->first();

            if ($lastUnsubscribed && Carbon::parse($lastUnsubscribed->unsubscribe_date)->greaterThan(Carbon::now()->subHours(24))) {
                throw ValidationException::withMessages(['amount' => trans('public.termination_message')]);
            }

            if ($subscriberHistory->contains('status', 'Subscribing')) {
                throw ValidationException::withMessages(['amount' => trans('public.subscribing_alert')]);
            }

            $deal = [];
            try {
                $deal = $metaService->createDeal($meta_login, $amount, 'Withdraw from trading account', dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error creating deal: ' . $e->getMessage());
            }

            $new_wallet_balance = $wallet->balance + $amount;

            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'to_wallet_id' => $wallet->id,
                'from_meta_login' => $meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'BalanceOut',
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
                'new_wallet_amount' => $new_wallet_balance,
            ]);

            $wallet->update(['balance' => $new_wallet_balance]);

        } else {
            $e_wallet = Wallet::where('user_id', $user->id)->where('type', 'e_wallet')->first();
            $amount_remain = $amount;

            if ($e_wallet) {
                $eWalletBalanceIn = Transaction::where('from_wallet_id', $e_wallet->id)
                    ->where('to_meta_login', $meta_login)
                    ->where('transaction_type', 'BalanceIn')
                    ->where('status', 'Success')
                    ->sum('transaction_amount');

                $eWalletBalanceOut = Transaction::where('to_wallet_id', $e_wallet->id)
                    ->where('from_meta_login', $meta_login)
                    ->where('transaction_type', 'BalanceOut')
                    ->where('status', 'Success')
                    ->sum('transaction_amount');

                $remainingBalance = $eWalletBalanceIn - $eWalletBalanceOut;

                $deal = [];
                try {
                    $deal = $metaService->createDeal($meta_login, $amount, 'Withdraw from trading account', dealAction::WITHDRAW);
                } catch (\Exception $e) {
                    \Log::error('Error creating deal: ' . $e->getMessage());
                }

                if ($remainingBalance > 0) {
                    if ($remainingBalance >= $amount) {
                        // Deduct full amount from e_wallet
                        $e_wallet->balance += $amount;
                        $e_wallet->save();

                        Transaction::create([
                            'category' => 'trading_account',
                            'user_id' => $user->id,
                            'to_wallet_id' => $e_wallet->id,
                            'from_meta_login' => $meta_login,
                            'ticket' => $deal['deal_Id'],
                            'transaction_number' => RunningNumberService::getID('transaction'),
                            'transaction_type' => 'BalanceOut',
                            'amount' => $amount,
                            'transaction_charges' => 0,
                            'transaction_amount' => $amount,
                            'status' => 'Success',
                            'comment' => $deal['conduct_Deal']['comment'],
                            'new_wallet_amount' => $e_wallet->balance,
                        ]);

                        $amount_remain = 0;
                    } else {
                        // Deduct partial amount from e_wallet
                        $e_wallet->balance += $remainingBalance;
                        $e_wallet->save();

                        Transaction::create([
                            'category' => 'trading_account',
                            'user_id' => $user->id,
                            'to_wallet_id' => $e_wallet->id,
                            'from_meta_login' => $meta_login,
                            'ticket' => $deal['deal_Id'],
                            'transaction_number' => RunningNumberService::getID('transaction'),
                            'transaction_type' => 'BalanceOut',
                            'amount' => $remainingBalance,
                            'transaction_charges' => 0,
                            'transaction_amount' => $remainingBalance,
                            'status' => 'Success',
                            'comment' => $deal['conduct_Deal']['comment'],
                            'new_wallet_amount' => $e_wallet->balance,
                        ]);

                        $amount_remain -= $remainingBalance;
                    }
                }
            }

            if ($amount_remain > 0) {
                $new_wallet_balance = $wallet->balance + $amount_remain;

                Transaction::create([
                    'category' => 'trading_account',
                    'user_id' => $user->id,
                    'to_wallet_id' => $wallet->id,
                    'from_meta_login' => $meta_login,
                    'ticket' => $deal['deal_Id'],
                    'transaction_number' => RunningNumberService::getID('transaction'),
                    'transaction_type' => 'BalanceOut',
                    'amount' => $amount_remain,
                    'transaction_charges' => 0,
                    'transaction_amount' => $amount_remain,
                    'status' => 'Success',
                    'comment' => $deal['conduct_Deal']['comment'],
                    'new_wallet_amount' => $new_wallet_balance,
                ]);

                $wallet->update(['balance' => $new_wallet_balance]);
            }
        }

        return redirect()->back()
            ->with('title', trans('public.success_withdraw'))
            ->with('success', trans('public.successfully_withdraw') . ' $' . number_format($amount, 2) . trans('public.from_login') . ': ' . $meta_login);
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

        // check subscriber
        $subscriber = Subscriber::with(['master:id,meta_login', 'tradingAccount'])
            ->where('user_id', $user->id)
            ->where('meta_login', $to_trading_account->meta_login)
            ->whereIn('status', ['Pending', 'Subscribing'])
            ->first();

        if ($subscriber && $subscriber->status == 'Pending') {
            $subscriber->initial_meta_balance += $amount;
            $subscriber->save();
        } elseif ($subscriber && $subscriber->status == 'Subscribing') {
            $subscriber->subscribe_amount += $amount;
            $subscriber->save();

            $subscriptions = Subscription::with(['master:id,meta_login', 'tradingAccount'])
                ->where('user_id', $user->id)
                ->where('meta_login', $to_trading_account->meta_login)
                ->whereIn('status', ['Pending', 'Active'])
                ->get();

            if ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $subscription->meta_balance += $amount;
                    $subscription->save();
                    if ($subscription->status == 'Active') {
                        CopyTradeTransaction::create([
                            'user_id' => $user->id,
                            'trading_account_id' => $subscription->tradingAccount->id,
                            'meta_login' => $subscription->tradingAccount->meta_login,
                            'subscription_id' => $subscription->id,
                            'master_id' => $subscription->master->id,
                            'master_meta_login' => $subscription->master->meta_login,
                            'amount' => $amount,
                            'real_fund' => $amount,
                            'demo_fund' => 0,
                            'type' => 'Deposit',
                            'status' => 'Success',
                        ]);

                        SubscriptionBatch::create([
                            'user_id' => $user->id,
                            'trading_account_id' => $subscriber->trading_account_id,
                            'meta_login' => $to_trading_account->meta_login,
                            'meta_balance' => $amount,
                            'real_fund' => $amount,
                            'demo_fund' => 0,
                            'master_id' => $subscriber->master_id,
                            'master_meta_login' => $subscriber->master_meta_login,
                            'type' => 'CopyTrade',
                            'subscriber_id' => $subscriber->id,
                            'subscription_id' => $subscription->id,
                            'subscription_number' => $subscription->subscription_number,
                            'subscription_period' => $subscriber->roi_period,
                            'transaction_id' => $subscriber->transaction_id,
                            'subscription_fee' => $subscriber->initial_subscription_fee,
                            'settlement_start_date' => now(),
                            'settlement_date' => now()->addDays($subscriber->roi_period)->endOfDay(),
                            'status' => 'Active',
                            'approval_date' => now(),
                        ]);
                    }
                }
            }
        }

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
                ->whereDoesntHave('subscription', function ($subQuery) {
                    $subQuery->whereIn('status', ['Pending', 'Active']);
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
                'label' => $tradingAccount->meta_login . ' ($' . number_format($tradingAccount->balance, 2) . ')',
            ];
        });
    }

    public function becomeMaster(Request $request)
    {
        $rules = [
            'meta_login' => 'required',
            'min_join_equity' => 'required|numeric',
            'roi_period' => 'required|numeric',
        ];

        $attributes = [
            'meta_login' => trans('public.meta_login'),
            'min_join_equity' => trans('public.min_join_equity'),
            'roi_period' => trans('public.roi_period'),
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->setAttributeNames($attributes);

        // Redirect back if validation fails
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {

            $trading_account = TradingAccount::where('meta_login', $request->meta_login)->first();

            $existingRequest = MasterRequest::where('trading_account_id', $trading_account->id)
                ->where('status', 'pending')
                ->exists();

            if ($existingRequest) {
                    throw ValidationException::withMessages(['meta_login' => trans('public.become_master_request_pending'),]);
            }

            MasterRequest::create([
                'user_id' =>  Auth::id(),
                'trading_account_id' =>  $trading_account->id,
                'min_join_equity' => $request->min_join_equity,
                'roi_period' => $request->roi_period,
            ]);
        }

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
            'roi_period' => $request->roi_period,
            'signal_status' => $request->signal_status,
        ]);

        return redirect()->back()
            ->with('title', trans('public.success_configure_setting'))
            ->with('success', trans('public.successfully_configure_setting') . ': ' . $master->meta_login);
    }

    public function getRequestHistory(Request $request)
    {
        $user = Auth::user();

        $masterRequest = MasterRequest::with(['trading_account:id,meta_login', 'trading_account.tradingUser:meta_login,name,company'])
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

    protected function createTransaction($userId, $fromWalletId, $toMetaLogin, $ticket, $transactionType, $amount, $newWalletAmount, $comment, $transactionCharges, $category) {
        Transaction::create([
            'category' => $category,
            'user_id' => $userId,
            'from_wallet_id' => $fromWalletId,
            'to_meta_login' => $toMetaLogin,
            'ticket' => $ticket,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'transaction_charges' => $transactionCharges,
            'transaction_amount' => $amount,
            'status' => 'Success',
            'comment' => $comment,
            'new_wallet_amount' => $newWalletAmount,
        ]);
    }

    public function updateLeverage(Request $request)
    {
        $meta_login = $request->meta_login;
        $leverage = $request->leverage;
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $metaService->updateLeverage($meta_login, $leverage);

        return redirect()->back()
            ->with('title', trans('public.success_edit_leverage'))
            ->with('success', trans('public.successfully_edit_leverage'));
    }

    public function changePassword(Request $request)
    {
        $rules = [
            'meta_login' => ['required'],
            'master_password' => ['required_without:investor_password'],
            'confirm_master_password' => ['required_with:master_password', 'same:master_password'],
            'investor_password' => ['required_without:master_password'],
            'confirm_investor_password' => ['required_with:investor_password', 'same:investor_password'],
            'security_pin' => ['required'],
        ];

        if (!empty($request->master_password)) {
            $rules['master_password'][] = Password::min(8)->letters()->symbols()->numbers()->mixedCase();
        }
        if (!empty($request->investor_password)) {
            $rules['investor_password'][] = Password::min(8)->letters()->symbols()->numbers()->mixedCase();
        }

        $attributes = [
            'meta_login' => trans('public.meta_login'),
            'master_password' => trans('public.master_password'),
            'confirm_master_password' => trans('public.confirm_master_password'),
            'investor_password' => trans('public.investor_password'),
            'confirm_investor_password' => trans('public.confirm_investor_password'),
            'security_pin' => trans('public.security_pin'),
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributes);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $user = Auth::user();

            if (!is_null($user->security_pin) && !Hash::check($request->get('security_pin'), $user->security_pin)) {
                throw ValidationException::withMessages(['security_pin' => trans('public.current_pin_invalid')]);
            }

            $meta_login = $request->meta_login;
            $master_password = $request->master_password;
            $investor_password = $request->investor_password;
            $metaService = new MetaFiveService();
            $connection = $metaService->getConnectionStatus();

            if ($connection != 0) {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            if ($master_password) {
                $metaService->changePassword($meta_login, 0, $master_password);
            }

            if ($investor_password) {
                $metaService->changePassword($meta_login, 1, $investor_password);
            }

            Notification::route('mail', $user->email)
                ->notify(new ChangeTradingAccountPassowrdNotification($user, $meta_login, $master_password, $investor_password));

            // Return success message
            return redirect()->back()
                ->with('title', trans('public.success_change_password'))
                ->with('success', trans('public.successfully_change_password'));
        }
    }
    }
