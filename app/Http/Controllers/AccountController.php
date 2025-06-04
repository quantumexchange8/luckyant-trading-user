<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTradingAccountRequest;
use App\Http\Requests\DepositBalanceRequest;
use App\Models\AccountType;
use App\Models\CopyTradeTransaction;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Notifications\AddTradingAccountNotification;
use App\Notifications\DepositRequestNotification;
use App\Services\AlphaFundService;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        $live_account_quota = Setting::where('slug', 'live_account_quota')->first();

        $ids = [793, 247];
        $enableVirtual = false;

        foreach ($ids as $id) {
            if (\Auth::user()->id == $id || str_contains(\Auth::user()->hierarchyList, "-$id-")) {
                $enableVirtual = true;
                break;
            }
        }

        $fundDetails = AlphaFundService::calculateRemainingQuota(Auth::user());

        return Inertia::render('Account/Account', [
            'activeAccountCounts' => TradingUser::where('user_id', Auth::id())->where('acc_status', 'Active')->count(),
            'liveAccountQuota' => (integer) $live_account_quota->value,
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'leverageSel' => (new SelectOptionService())->getActiveLeverageSelection(),
            'masterAccountLogin' => Master::where('user_id', Auth::id())->pluck('meta_login')->toArray(),
            'enableVirtualAccount' => $enableVirtual,
            'alphaDepositMax' => $fundDetails['available_deposit_balance'],
            'alphaDepositQuota' => $fundDetails['remaining_quota']
        ]);
    }

    public function createAccount(AddTradingAccountRequest $request)
    {
        $user = Auth::user();

        $liveAccountQuota = Setting::where('slug', 'live_account_quota')->first()->value;

        if ($user->tradingUsers->where('acc_status', 'Active')->count() >= $liveAccountQuota) {
            return redirect()->back()
                ->with('title', trans('public.live_account_quota'))
                ->with('warning', trans('public.live_account_quota_warning'));
        }

        $type = $request->account_type;
        $account_type = AccountType::firstWhere('slug', $type);
        $leverage = $request->leverage;

        if ($type != 'virtual') {
            $metaService = new MetaFiveService();
            $connection = $metaService->getConnectionStatus();

            if ($connection != 0) {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            $metaAccount = $metaService->createUser($user, $account_type->name, $leverage, $user->email);
            $balance = TradingAccount::where('meta_login', $metaAccount['login'])->value('balance') ?? 0;

            if (!$account_type->allow_trade) {
                $metaService->disableTrade($metaAccount['login']);
            }

            Notification::route('mail', $user->email)
                ->notify(new AddTradingAccountNotification($metaAccount, $balance, $user));

            return back()->with('toast', [
                'title' => trans("public.success"),
                'message' => trans('public.created_trading_account'),
                'type' => 'success',
            ]);
        } else {
            $virtual_account = RunningNumberService::getID('virtual_account');

            TradingAccount::create([
                'user_id' => $user->id,
                'meta_login' => $virtual_account,
                'account_type' => $account_type->id,
                'margin_leverage' => $leverage,
            ]);

            TradingUser::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'meta_login' => $virtual_account,
                'meta_group' => $account_type->name,
                'account_type' => $account_type->id,
                'leverage' => $leverage,
            ]);

            return back()->with('toast', [
                'title' => trans("public.success"),
                'message' => trans('public.created_virtual_account'),
                'type' => 'success',
            ]);
        }
    }

    public function getTradingAccountsData()
    {
        $user = Auth::user();
        $userTradingAccounts = TradingUser::select('id', 'user_id', 'meta_login', 'acc_status', 'account_type')
            ->where('user_id', $user->id)
            ->where('acc_status', 'Active')
            ->whereNot('account_type', 2)
            ->get();

        $connection = (new MetaFiveService())->getConnectionStatus();

        if ($connection == 0) {
            try {
                (new MetaFiveService())->getUserInfo($userTradingAccounts);
            } catch (\Exception $e) {
                \Log::error('Error update trading accounts: '. $e->getMessage());
            }
        }

        $tradingAccounts = TradingAccount::with([
            'tradingUser:id,user_id,name,meta_login,company,acc_status',
            'masterRequest:id,trading_account_id,status',
            'masterAccount:id,trading_account_id',
            'accountType:id,slug',
        ])
            ->where('user_id', $user->id)
            ->whereDoesntHave('masterAccount', function ($query) {
                $query->whereNotNull('trading_account_id');
            })
            ->whereHas('tradingUser', function ($query) {
                $query->where('acc_status','Active');
            })
            ->latest()
            ->get()
            ->map(function ($tradingAccount) {
                // active subscriber
                $active_subscriber = Subscriber::where('meta_login', $tradingAccount->meta_login);
                // latest unsubscribed
                $latest_unsubscribed = $active_subscriber->clone()
                    ->where('status', 'Unsubscribed')
                    ->latest()
                    ->first();
                // subscribing subscribers
                $latest_subscribing = $active_subscriber->clone()
                    ->with(['master:id,meta_login,trading_account_id', 'master.tradingUser:id,name,meta_login,company'])
                    ->where('status', 'Subscribing')
                    ->latest()
                    ->first();

                // pamm subscriptions exist
                $pamm_subscription_exist = PammSubscription::where('meta_login', $tradingAccount->meta_login)
                    ->whereIn('status', ['Pending', 'Active'])
                    ->exists();

                // active pamm
                $active_pamm = PammSubscription::where('meta_login', $tradingAccount->meta_login)
                    ->where('status', 'Active')
                    ->with(['master:id,meta_login,trading_account_id', 'master.tradingUser:id,name,meta_login,company'])
                    ->latest()
                    ->first();

                $data = $tradingAccount;

                if ($pamm_subscription_exist) {
                    $data['balance_in'] = false;
                    $data['balance_out'] = false;
                } elseif ($latest_unsubscribed && Carbon::parse($latest_unsubscribed->unsubscribe_date)->greaterThan(Carbon::now()->subHours(24))) {
                    $data['balance_in'] = true;
                    $data['balance_out'] = false;
                } elseif ($active_subscriber->clone()->where('status', 'Pending')->exists()) {
                    $data['balance_in'] = false;
                    $data['balance_out'] = false;
                } elseif ($active_subscriber->clone()->whereIn('status', ['Subscribing', 'Expiring'])->exists() && $tradingAccount->account_type == 3) {
                    $data['balance_in'] = false;
                    $data['balance_out'] = false;
                } elseif ($active_subscriber->clone()->whereIn('status', ['Subscribing', 'Expiring'])->exists()) {
                    $data['balance_in'] = true;
                    $data['balance_out'] = false;
                } elseif ($tradingAccount->demo_fund > 0) {
                    $data['balance_in'] = true;
                    $data['balance_out'] = false;
                } else {
                    $data['balance_in'] = true;
                    $data['balance_out'] = true;
                }

                $data['active_subscriber'] = $latest_subscribing;
                $data['pamm_subscription'] = $active_pamm;

                return $data;
            });

        return response()->json([
            'tradingAccounts' => $tradingAccounts,
            'totalEquity' => $tradingAccounts->sum('equity'),
            'totalBalance' => $tradingAccounts->sum('balance'),
        ]);
    }

    public function getAccountReport(Request $request)
    {
        $meta_login = $request->query('meta_login');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $type = $request->query('type');

        $query = Transaction::query()
            ->whereIn('transaction_type', ['BalanceIn', 'BalanceOut', 'InternalTransfer', 'TopUp', 'ManagementFee'])
            ->where('status', 'Success');

        if ($meta_login) {
            $query->where(function($subQuery) use ($meta_login) {
                $subQuery->where('from_meta_login', $meta_login)
                    ->orWhere('to_meta_login', $meta_login);
            });
        }

        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        // Apply type filter
        if ($type && $type !== 'all') {
            switch ($type) {
                case 'balance_in':
                    $query->where('transaction_type', 'BalanceIn');
                    break;
                case 'balance_out':
                    $query->where('transaction_type', 'BalanceOut');
                    break;
                case 'internal_transfer':
                    $query->where('transaction_type', 'InternalTransfer');
                    break;
                case 'top_up':
                    $query->where('transaction_type', 'TopUp');
                    break;
                case 'management_fee':
                    $query->where('transaction_type', 'ManagementFee');
                    break;
            }
        }

        $transactions = $query
            ->latest()
            ->get()
            ->map(function ($transaction) {
                return [
                    'category' => $transaction->category,
                    'transaction_type' => \Str::snake($transaction->transaction_type),
                    'from_meta_login' => $transaction->from_meta_login,
                    'to_meta_login' => $transaction->to_meta_login,
                    'transaction_number' => $transaction->transaction_number,
                    'payment_account_id' => $transaction->payment_account_id,
                    'from_wallet_address' => $transaction->from_wallet_address,
                    'to_wallet_address' => $transaction->to_wallet_address,
                    'txn_hash' => $transaction->txn_hash,
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'status' => $transaction->status,
                    'comment' => $transaction->comment,
                    'remarks' => $transaction->remarks,
                    'created_at' => $transaction->created_at,
                    'wallet_name' => $transaction->payment_account->payment_account_name ?? '-'
                ];
            });

        return response()->json($transactions);
    }

    public function depositBalance(DepositBalanceRequest $request)
    {
        $user = Auth::user();
        $wallet = Wallet::find($request->wallet_id);
        $amount = $request->amount;
        $meta_login = $request->to_meta_login;
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

        /*
         * Alpha acc deposit is 20% of total active fund from PROFIT/BONUS amount;
         * */
        if ($trading_account->accountType->slug == 'alpha') {
            $fundDetails = AlphaFundService::calculateRemainingQuota($user);

            // Validate the amount
            if ($amount > $fundDetails['available_deposit_balance'] || $amount > $fundDetails['remaining_quota']) {
                throw ValidationException::withMessages(['amount' => trans('public.amount_entered_exceed')]);
            }

            $passBalanceIn = Transaction::where([
                'category' => 'trading_account',
                'transaction_type' => 'BalanceIn',
                'to_meta_login' => $meta_login,
                'status' => 'Success'
            ])
                ->first();

            if (empty($passBalanceIn)) {
                $minAmount = 100;
            } else {
                $minAmount = 10;
            }

            if ($amount < $minAmount) {
                throw ValidationException::withMessages(['amount' => trans('public.min_amount_error')]);
            }

            if ($amount % 10 != 0) {
                throw ValidationException::withMessages(['amount' => trans('public.amount_multiples_of_10')]);
            }
        } else {
            $minAmount = $amount % 100;
            if ($minAmount != 0) {
                throw ValidationException::withMessages(['amount' => trans('public.amount_multiples_of_100')]);
            }
        }

        // pamm subscriptions exist
        $pamm_subscription_exist = PammSubscription::where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Active'])
            ->exists();

        if ($pamm_subscription_exist) {
            return back()->with('toast', [
                'title' => trans("public.invalid_action"),
                'message' => trans('public.try_again_later'),
                'type' => 'warning',
            ]);
        }

        // conduct deal and transaction record
        $deal = [];

        if ($trading_account->accountType->slug != 'virtual') {
            $connection = (new MetaFiveService())->getConnectionStatus();

            if (!$trading_account->accountType->requires_approval) {
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
            }
        } else {
            $trading_account->update(['balance' => $trading_account->balance + $amount]);
        }

        $dealId = $deal['deal_Id'] ?? null;
        if (
            !$dealId &&
            $trading_account->accountType->slug != 'virtual' &&
            !$trading_account->accountType->requires_approval
        ) {
            return redirect()->back()
                ->with('title', trans('public.deposit_fail'))
                ->with('warning', trans('public.balance_in_fail'));
        }

        $comment = $deal['conduct_Deal']['comment'] ?? 'Deposit to trading account';

        if ($wallet->type == 'e_wallet' && $trading_account->accountType->slug != 'virtual') {
            $this->createTransaction($user->id, $wallet->id, $meta_login, $dealId, 'BalanceIn', $eWalletAmount, $wallet->balance - $eWalletAmount, $comment, 0, 'trading_account');
            $wallet->balance -= $eWalletAmount;
            $wallet->save();

            $this->createTransaction($user->id, $cash_wallet->id, $meta_login, $dealId, 'BalanceIn', $cashWalletAmount, $cash_wallet->balance - $cashWalletAmount, $comment, 0, 'trading_account');
            $cash_wallet->balance -= $cashWalletAmount;
            $cash_wallet->save();
        } else {
            $transaction = Transaction::create([
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
                'new_wallet_amount' => $wallet->balance - $amount,
            ]);

            if ($trading_account->accountType->requires_approval) {
                $transaction->update([
                   'status' => 'Processing',
                ]);

                Notification::route('mail', 'sluckyant@gmail.com')
                    ->notify(new DepositRequestNotification($transaction));
            } else {
                $transaction->update([
                    'status' => 'Success',
                    'comment' => $comment,
                ]);
            }

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

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.successfully_deposit') . ' $' . number_format($amount, 2) . trans('public.to_login') . ': ' . $request->to_meta_login,
            'type' => 'success',
        ]);
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

    public function withdrawBalance(Request $request)
    {
        Validator::make($request->all(), [
            'amount' => ['required', 'numeric', ],
            'from_meta_login' => ['required'],
        ])->setAttributeNames([
            'amount' => trans('public.amount'),
            'from_meta_login' => trans('public.meta_login'),
        ])->validate();

        $user = Auth::user();
        $user->load(['wallets' => function ($query) {
            $query->whereIn('type', ['cash_wallet', 'e_wallet']);
        }]);

        $cash_wallet = $user->wallets->firstWhere('type', 'cash_wallet');
        $e_wallet = $user->wallets->firstWhere('type', 'e_wallet');
        $amount = $request->amount;
        $trading_account = TradingAccount::with([
            'subscriber',
            'pamm_subscription',
            'accountType'
        ])->firstWhere('meta_login', $request->from_meta_login);

        // Check balance
        if ($trading_account->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        // Check pending subscription
        $pending_subscriber = $trading_account->subscriber()->where('status', 'Pending')
            ->exists();

        $pending_pamm = $trading_account->pamm_subscription()->where('status', 'Pending')
            ->exists();

        if ($pending_subscriber || $pending_pamm) {
            throw ValidationException::withMessages(['amount' => trans('public.pending_subscription_approval')]);
        }

        // Check last subscription
        $last_terminated_copy_trade = $trading_account->subscriber()
            ->where('status', 'Unsubscribed')
            ->latest()
            ->first();

        $last_terminated_pamm = $trading_account->pamm_subscription()
            ->whereIn('status', ['Terminated', 'Revoked'])
            ->latest()
            ->first();

        if ($last_terminated_copy_trade) {
            if (Carbon::parse($last_terminated_copy_trade->unsubscribe_date)->greaterThan(Carbon::now()->subHours(24))) {
                throw ValidationException::withMessages(['amount' => trans('public.termination_within_one_day')]);
            }
        }

        if ($last_terminated_pamm) {
            if (Carbon::parse($last_terminated_pamm->termination_date)->greaterThan(Carbon::now()->subHours(24))) {
                throw ValidationException::withMessages(['amount' => trans('public.termination_within_one_day')]);
            }
        }

        // Check active subscription
        $active_subscriber = $trading_account->subscriber()->where('status', 'Subscribing')->exists();
        $active_pamm = $trading_account->pamm_subscription()->where('status', 'Active')->exists();

        if ($active_subscriber) {
            throw ValidationException::withMessages(['amount' => trans('public.account_have_subscription')]);
        }

        if ($active_pamm) {
            throw ValidationException::withMessages(['amount' => trans('public.account_have_subscription')]);
        }

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans("public.server_under_maintenance"),
                'message' => trans('public.try_again_later'),
                'type' => 'warning',
            ]);
        }

        $dealId = null;
        $account_type = $trading_account->accountType->slug;
        $comment = 'Withdraw from trading account';

        if ($account_type != 'virtual') {
            try {
                $metaService->getUserInfo(collect([$trading_account]));
                $deal = $metaService->createDeal($trading_account->meta_login, $amount, $comment, dealAction::WITHDRAW);
                $dealId = $deal['deal_Id'] ?? null;
                $comment = $deal['conduct_Deal']['comment'] ?? 'Withdraw from trading account';
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts or creating deal: ' . $e->getMessage());
            }
        } else {
            $trading_account->update([
                'balance' => $trading_account->balance - $amount
            ]);
        }

        $amount_remain = $amount;

        // Only if $e_wallet exists and has positive remaining allocation
        if ($e_wallet) {
            $lastTerminationReturn = Transaction::where('from_meta_login', $trading_account->meta_login)
                ->where('transaction_type', 'TerminationReturn')
                ->where('status', 'Success')
                ->latest()
                ->first();

            $eWalletBalanceInQuery = Transaction::where('from_wallet_id', $e_wallet->id)
                ->where('to_meta_login', $trading_account->meta_login)
                ->where('transaction_type', 'BalanceIn')
                ->where('status', 'Success');

            $eWalletBalanceOutQuery = Transaction::where('to_wallet_id', $e_wallet->id)
                ->where('from_meta_login', $trading_account->meta_login)
                ->where('transaction_type', 'BalanceOut')
                ->where('status', 'Success');

            if ($lastTerminationReturn) {
                $eWalletBalanceInQuery->where('created_at', '>', $lastTerminationReturn->created_at);
                $eWalletBalanceOutQuery->where('created_at', '>', $lastTerminationReturn->created_at);
            }

            $eWalletBalanceIn = $eWalletBalanceInQuery->sum('transaction_amount');
            $eWalletBalanceOut = $eWalletBalanceOutQuery->sum('transaction_amount');

            $remainingBalance = $eWalletBalanceIn - $eWalletBalanceOut;

            if ($remainingBalance > 0) {
                $transferAmount = min($remainingBalance, $amount_remain);

                $e_wallet->increment('balance', $transferAmount);

                Transaction::create([
                    'category' => 'trading_account',
                    'user_id' => $user->id,
                    'to_wallet_id' => $e_wallet->id,
                    'from_meta_login' => $trading_account->meta_login,
                    'ticket' => $dealId,
                    'transaction_number' => RunningNumberService::getID('transaction'),
                    'transaction_type' => 'BalanceOut',
                    'amount' => $transferAmount,
                    'transaction_charges' => 0,
                    'transaction_amount' => $transferAmount,
                    'status' => 'Success',
                    'comment' => $comment,
                    'new_wallet_amount' => $e_wallet->balance,
                ]);

                $amount_remain -= $transferAmount;
            }
        }

        // If any remaining, transfer to cash wallet
        if ($amount_remain > 0 && $cash_wallet) {
            $cash_wallet->increment('balance', $amount_remain);

            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'to_wallet_id' => $cash_wallet->id,
                'from_meta_login' => $trading_account->meta_login,
                'ticket' => $dealId,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'BalanceOut',
                'amount' => $amount_remain,
                'transaction_charges' => 0,
                'transaction_amount' => $amount_remain,
                'status' => 'Success',
                'comment' => $comment,
                'new_wallet_amount' => $cash_wallet->balance,
            ]);
        }

        return back()->with('toast', [
            'title' => trans("public.success_withdraw"),
            'message' => trans('public.successfully_withdraw') . ' $' . number_format($amount, 2) . trans('public.from_login') . ': ' . $trading_account->meta_login,
            'type' => 'success',
        ]);
    }
}
