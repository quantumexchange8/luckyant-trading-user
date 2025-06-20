<?php

namespace App\Http\Controllers;

use App\Models\MasterLeader;
use App\Models\PammSubscription;
use App\Models\Subscriber;
use App\Models\SubscriptionBatch;
use App\Models\User;
use App\Services\SidebarService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        $ids = [793, 247];
        $enableVirtual = false;

        foreach ($ids as $id) {
            if (\Auth::user()->id == $id || strpos(\Auth::user()->hierarchyList, "-$id-") !== false) {
                $enableVirtual = true;
                break;
            }
        }

        return Inertia::render('AccountInfo/AccountInfo', [
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'leverageSel' => (new SelectOptionService())->getActiveLeverageSelection(),
            'accountCounts' => Auth::user()->tradingUsers->where('acc_status', 'Active')->count(),
            'masterAccountLogin' => Master::where('user_id', Auth::id())->pluck('meta_login')->toArray(),
            'liveAccountQuota' => Setting::where('slug', 'live_account_quota')->first(),
            'totalEquity' => $tradingAccounts->sum('equity'),
            'totalBalance' => $tradingAccounts->sum('balance'),
            'virtualStatus' => $enableVirtual,
        ]);
    }

    public function add_trading_account(AddTradingAccountRequest $request)
    {
        $user = Auth::user();

        $liveAccountQuota = Setting::where('slug', 'live_account_quota')->first()->value;

        if ($user->tradingUsers->where('acc_status', 'Active')->count() >= $liveAccountQuota) {
            return redirect()->back()
                ->with('title', trans('public.live_account_quota'))
                ->with('warning', trans('public.live_account_quota_warning'));
        }

        $type = $request->type;
        $group = AccountType::with('metaGroup')->where('id', $type)->get()->value('metaGroup.meta_group_name');
        $leverage = $request->leverage;

        if ($type == 1){
            $metaService = new MetaFiveService();
            $connection = $metaService->getConnectionStatus();

            if ($connection != 0) {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            $metaAccount = $metaService->createUser($user, $group, $leverage, $user->email);
            $balance = TradingAccount::where('meta_login', $metaAccount['login'])->value('balance');

            Notification::route('mail', $user->email)
                ->notify(new AddTradingAccountNotification($metaAccount, $balance, $user));

                return back()->with('toast', trans('public.created_trading_account'));
        }
        else{
            $virtual_account = RunningNumberService::getID('virtual_account');

            TradingAccount::create([
                'user_id' => $user->id,
                'meta_login' => $virtual_account,
                'account_type' => $type,
                'margin_leverage' => $leverage,
            ]);

            TradingUser::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'meta_login' => $virtual_account,
                'meta_group' => $group,
                'account_type' => $type,
                'leverage' => $leverage,
            ]);

            return back()->with('toast', trans('public.created_virtual_account'));
        }
    }

    public function refreshTradingAccountsData()
    {
        return $this->getTradingAccountsData();
    }

    protected function getTradingAccountsData()
    {
        $user = Auth::user();
        $userTradingAccounts = $user->tradingAccounts;

        $activeTradingAccounts = $userTradingAccounts->filter(function ($account) {
            return $account->tradingUser['acc_status'] === 'Active' && $account->tradingUser['account_type'] === 1;
        });

        $connection = (new MetaFiveService())->getConnectionStatus();

        if ($connection == 0) {
            try {
                (new MetaFiveService())->getUserInfo($activeTradingAccounts);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }

        $tradingAccounts = TradingAccount::with(['tradingUser:id,user_id,name,meta_login,company,acc_status', 'masterRequest:id,trading_account_id,status'])
            ->where('user_id', \Auth::id())
            ->whereDoesntHave('masterAccount', function ($query) {
                $query->whereNotNull('trading_account_id');
            })
            ->whereHas('tradingUser', function ($query) {
                $query->where('acc_status','Active');
            })
            ->latest()
            ->get();

        $tradingAccounts->each(function ($tradingAccount) {
            $activeSubscriber = Subscriber::with(['master', 'master.tradingUser'])
                ->where('meta_login', $tradingAccount->meta_login);

            $pammSubscription = PammSubscription::where('meta_login', $tradingAccount->meta_login);

            // Get the latest unsubscribed and subscribing subscribers
            $latestUnsubscribed = $activeSubscriber->clone()->where('status', 'Unsubscribed')->latest()->first();
            $latestSubscribing = $activeSubscriber->clone()->where('status', 'Subscribing')->latest()->first();

            // Check if the latest unsubscribed subscriber exists and its unsubscribe date is within the last 24 hours
            if ($latestUnsubscribed && \Carbon\Carbon::parse($latestUnsubscribed->unsubscribe_date)->greaterThan(\Carbon\Carbon::now()->subHours(24))) {
                $tradingAccount->balance_out = false;
                $tradingAccount->balance_in = true;
            } elseif ($activeSubscriber->whereIn('status', ['Subscribing', 'Expiring', 'Pending'])->exists()) {
                $tradingAccount->balance_out = false;
                $tradingAccount->balance_in = true;
            } elseif ((clone $pammSubscription)->whereIn('status', ['Pending', 'Active'])->exists()) {
                $tradingAccount->balance_out = false;
                $tradingAccount->balance_in = false;
            } elseif ($tradingAccount->demo_fund > 0) {
                $tradingAccount->balance_out = false;
                $tradingAccount->balance_in = true;
            } else {
                $tradingAccount->balance_out = true;
                $tradingAccount->balance_in = true;
            }

            // Set the latest subscribing subscriber
            $tradingAccount->active_subscriber = $latestSubscribing;
            $tradingAccount->pamm_subscription = $pammSubscription->where('status', 'Active')->with(['master', 'master.tradingUser'])->latest()->first();
        });

        $masterAccounts = Master::with(['tradingAccount', 'tradingAccount.accountType:id,group_id,name', 'tradingUser:id,user_id,name,meta_login,company,acc_status'])->where('user_id', \Auth::id())->get();

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

    protected function getInactiveAccountsData()
    {
        $user = Auth::user();

        $tradingAccounts = TradingAccount::with(['tradingUser:id,user_id,name,meta_login,company,acc_status', 'masterRequest:id,trading_account_id,status'])
            ->where('user_id', \Auth::id())
            ->whereDoesntHave('masterAccount', function ($query) {
                $query->whereNotNull('trading_account_id');
            })
            ->whereHas('tradingUser', function ($query) {
                $query->where('acc_status','Deleted');
            })
            ->latest()
            ->get();

        return response()->json([
            'tradingAccounts' => $tradingAccounts,
        ]);
    }

    public function depositTradingAccount(DepositBalanceRequest $request)
    {
        $user = Auth::user();
        $wallet = Wallet::find($request->wallet_id);
        $amount = $request->amount;
        $meta_login = $request->to_meta_login;
        $type = $request->type;
        $trading_account = TradingAccount::where('meta_login', $meta_login)->first();
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

        // Remember to check on equity
        if ($trading_account->account_type == 1) {
            $connection = (new MetaFiveService())->getConnectionStatus();
            if ($connection == 0) {
                try {
                    $deal = (new MetaFiveService())->createDeal($meta_login, $amount, 'Deposit to trading account', dealAction::DEPOSIT);
                } catch (\Exception $e) {
                    \Log::error('Error fetching trading accounts: ' . $e->getMessage());
                }
            } else {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }
        } else {
            $trading_account->update(['balance' => $trading_account->balance + $amount]);
        }

        $dealId = $deal['deal_Id'] ?? null;
        if (!$dealId) {
            return redirect()->back()
                ->with('title', trans('public.deposit_fail'))
                ->with('warning', trans('public.balance_in_fail'));
        }

        $comment = $deal['conduct_Deal']['comment'] ?? 'Deposit to trading account';

        if ($wallet->type == 'e_wallet') {
            $this->createTransaction($user->id, $wallet->id, $meta_login, $dealId, 'BalanceIn', $eWalletAmount, $wallet->balance - $eWalletAmount, $comment, 0, 'trading_account');
            $wallet->balance -= $eWalletAmount;
            $wallet->save();

            $this->createTransaction($user->id, $cash_wallet->id, $meta_login, $dealId, 'BalanceIn', $cashWalletAmount, $cash_wallet->balance - $cashWalletAmount, $comment, 0, 'trading_account');
            $cash_wallet->balance -= $cashWalletAmount;
            $cash_wallet->save();
        } else {
            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
                'to_meta_login' => $meta_login,
                'ticket' => $dealId,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'BalanceIn',
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'Success',
                'comment' => $comment,
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

            $subscription = Subscription::with(['master:id,meta_login', 'tradingAccount'])
                ->where('user_id', $user->id)
                ->where('meta_login', $meta_login)
                ->where('master_id', $subscriber->master_id)
                ->where('status', 'Active')
                ->first();

            if ($subscription) {
                $subscription->meta_balance += $amount;
                $subscription->save();

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
                    'approval_date' => now() < $subscription->approval_date ? now()->addDay() : now(),
                ]);
            }
        }

        return redirect()->back()
            ->with('title', trans('public.success_deposit'))
            ->with('success', trans('public.successfully_deposit') . ' $' . number_format($amount, 2) . trans('public.to_login') . ': ' . $request->to_meta_login);
    }

    public function internalTransferTradingAccount(InternalTransferBalanceRequest $request)
    {
        $user = Auth::user();
        $from_trading_account = TradingAccount::where('meta_login', $request->from_meta_login)->first();
        $to_trading_account = TradingAccount::where('meta_login', $request->to_meta_login['value'])->first();
        $amount = $request->amount;
        $type = $from_trading_account->accountType->id;
        $to_type = $to_trading_account->accountType->id;

        dd($from_trading_account, $to_trading_account);

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        if ($type == 1){
            try {
                $metaService->getUserInfo(collect([$from_trading_account]));
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }

        // Check if balance is sufficient
        if ($from_trading_account->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        $deal_1 = [];
        $deal_2 = [];
        if ($type == 1){
            try {
                $deal_1 = $metaService->createDeal($from_trading_account->meta_login, $amount, "Trading Account To Trading Account", dealAction::WITHDRAW);
            } catch (\Throwable $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }
        else{
            $from_trading_account->update(['balance' => $from_trading_account->balance - $amount]);
        }

        $dealId_1 = $deal_1['deal_Id'] ?? '0';
        $comment_1 = $deal_1['conduct_Deal']['comment'] ?? 'Trading Account To Trading Account';

        if ($to_type == 1){
            try {
                $deal_2 = $metaService->createDeal($to_trading_account->meta_login, $amount, "Trading Account To Trading Account", dealAction::DEPOSIT);
            } catch (\Throwable $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }
        }
        else{
            $to_trading_account->update(['balance' => $to_trading_account->balance + $amount]);
        }

        $dealId_2 = $deal_2['deal_Id'] ?? '0';
        $comment_2 = $deal_2['conduct_Deal']['comment'] ?? 'Trading Account To Trading Account';

        $ticket = $dealId_1 . ', ' . $dealId_2;

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
            'comment' => $comment_1,
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

        return back()->with('toast', [
            'title' => trans("public.success_internal_transaction"),
            'message' => trans('public.successfully_transfer') . ' $' . number_format($amount, 2) . trans('public.from_login') . ': ' . $request->from_meta_login . ' ' . trans('public.to_login') . ': ' . $request->to_meta_login['value'],
            'type' => 'success',
        ]);
    }

    public function getTradingAccounts(Request $request)
    {
        if ($request->type == 'internal_transfer') {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())
                ->whereDoesntHave('tradingUser', function ($subQuery) {
                    $subQuery->whereIn('acc_status', ['Deleted']);
                })
                ->whereNot('meta_login', $request->meta_login)->get();
        } elseif ($request->type == 'subscribe') {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())
                ->whereDoesntHave('subscription', function ($subQuery) {
                    $subQuery->whereIn('status', ['Pending', 'Active']);
                })
                ->whereDoesntHave('pamm_subscription', function ($subQuery) {
                    $subQuery->whereIn('status', ['Pending', 'Active']);
                })
                ->whereDoesntHave('tradingUser', function ($subQuery) {
                    $subQuery->whereIn('acc_status', ['Deleted']);
                })
                ->whereNot('meta_login', $request->meta_login)
                ->where('account_type', 1)
                ->get();
        } elseif ($request->type == 'pamm') {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())
                ->whereDoesntHave('pamm_subscription', function ($subQuery) {
                    $subQuery->whereIn('status', ['Pending', 'Active']);
                })
                ->whereDoesntHave('subscription', function ($subQuery) {
                    $subQuery->whereIn('status', ['Pending', 'Active']);
                })
                ->whereDoesntHave('tradingUser', function ($subQuery) {
                    $subQuery->whereIn('acc_status', ['Deleted']);
                })
                ->whereNot('meta_login', $request->meta_login)
                ->get();
        } else {
            $tradingAccount = TradingAccount::where('user_id', Auth::id())
                ->whereDoesntHave('tradingUser', function ($subQuery) {
                    $subQuery->whereIn('acc_status', ['Deleted']);
                })
                ->whereDoesntHave('subscriber', function ($subQuery) {
                    $subQuery->where('status', 'Pending');
                })
                ->whereDoesntHave('pamm_subscription', function ($subQuery) {
                    $subQuery->whereIn('status', ['Pending', 'Active']);
                })
                ->whereHas('accountType', function ($subQuery) {
                    $subQuery->whereIn('slug', [
                        'hofi'
                    ]);
                })
                ->get();
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
                'account_type' => $tradingAccount->account_type,
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
        $type = $request->type;

        if ($type == 1) {
            $metaService = new MetaFiveService();
            $connection = $metaService->getConnectionStatus();

            if ($connection != 0) {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            $metaService->updateLeverage($meta_login, $leverage);
        }
        else{
            $trading_account = TradingAccount::where('meta_login', $meta_login)->first();
            $trading_user = TradingUser::where('meta_login', $meta_login)->first();

            $trading_account->update(['margin_leverage' => $leverage]);
            $trading_user->update(['leverage' => $leverage]);
        }


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
