<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinPammRequest;
use App\Models\Master;
use App\Models\MasterSubscriptionPackage;
use App\Models\PammSubscription;
use App\Models\Subscriber;
use App\Models\SubscriptionBatch;
use App\Models\Term;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PammController extends Controller
{
    /**
     *
     * @param Request $request
     * @return string
     */
    private function getStrategyType(Request $request): string
    {
        $strategyTypeMapping = [
            'hofi_strategy' => 'HOFI',
            'alpha_strategy' => 'Alpha',
        ];

        $routeName = $request->route()->getName();

        foreach ($strategyTypeMapping as $prefix => $type) {
            if (str_starts_with($routeName, $prefix)) {
                return $type;
            }
        }

        abort(404, 'Invalid strategy type');
    }

    public function pamm_master_listing(Request $request)
    {
        $strategyType = $this->getStrategyType($request);

        $user = User::find(1137);
        if ($user) {
            $childrenIds = $user->getChildrenIds();
            $authUserId = Auth::id();

            if ($authUserId == $user->id || in_array($authUserId, $childrenIds)) {
                return redirect()->back();
            }
        }

        $masterQuery = Master::where([
            'category' => 'pamm',
            'type' => 'StandardGroup',
            'strategy_type' => $strategyType,
            'status' => 'Active',
        ]);

        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        $masterQuery->whereHas('visible_to_leaders', function ($q) use ($first_leader) {
            $q->where('user_id', $first_leader->id);
        });

        $mastersCount = $masterQuery->count();

        return Inertia::render('Pamm/PammMaster/PammMasterListing', [
            'mastersCount' => $mastersCount,
            'strategyType' => strtolower($strategyType),
            'pammType' => 'StandardGroup'
        ]);
    }

    public function esg_investment_portfolio(Request $request)
    {
        $strategyType = $this->getStrategyType($request);

        $user = User::find(1137);
        if ($user) {
            $childrenIds = $user->getChildrenIds();
            $authUserId = Auth::id();

            if ($authUserId == $user->id || in_array($authUserId, $childrenIds)) {
                return redirect()->back();
            }
        }

        $masterQuery = Master::where([
            'category' => 'pamm',
            'type' => 'ESG',
            'strategy_type' => $strategyType,
            'status' => 'Active',
        ]);

        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        $masterQuery->whereHas('visible_to_leaders', function ($q) use ($first_leader) {
            $q->where('user_id', $first_leader->id);
        });

        $mastersCount = $masterQuery->count();

        return Inertia::render('Pamm/PammMaster/PammMasterListing', [
            'mastersCount' => $mastersCount,
            'strategyType' => strtolower($strategyType),
            'pammType' => 'ESG'
        ]);
    }

    public function getPammMasters(Request $request)
    {
        // fetch limit with default
        $limit = $request->input('limit', 12);

        // Fetch parameter from request
        $search = $request->input('search', '');
        $sortType = $request->input('sortType');
        $tag = $request->input('tag', '');

        $strategyType = $this->getStrategyType($request);

        // Fetch paginated masters
        $mastersQuery = Master::query()
            ->with([
                'tradingUser:id,meta_login,name,account_type',
                'media',
                'masterManagementFee'
            ])
            ->withCount([
                'active_copy_trades',
                'active_pamm'
            ])
            ->withSum('active_copy_trades', 'subscribe_amount')
            ->withSum('active_pamm', 'subscription_amount')
            ->addSelect([
                'latest_profit' => DB::table('trade_histories')
                    ->select('trade_profit')
                    ->whereColumn('trade_histories.meta_login', 'masters.meta_login')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->where([
                'category' => 'pamm',
                'strategy_type' => $strategyType,
                'type' => $request->pamm_type,
                'status' => 'Active',
            ]);

        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        $mastersQuery->whereHas('visible_to_leaders', function ($q) use ($first_leader) {
            $q->where('user_id', $first_leader->id);
        });

        // Apply search parameter to multiple fields
        if (!empty($search)) {
            $keyword = '%' . $search . '%';
            $mastersQuery->where(function ($q) use ($keyword) {
                $q->whereHas('tradingUser', function ($account) use ($keyword) {
                    $account->where('meta_login', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword)
                        ->orWhere('company', 'like', $keyword);
                });
            });
        }

        // Apply sorting dynamically
        if (in_array($sortType, ['latest', 'largest_fund', 'most_investors'])) {
            switch ($sortType) {
                case 'latest':
                    $mastersQuery->orderBy('created_at', 'desc');
                    break;

                case 'largest_fund':
                    $mastersQuery->orderByDesc(DB::raw('COALESCE(active_copy_trades_sum_subscribe_amount, 0) + COALESCE(active_pamm_sum_subscription_amount, 0)'));
                    break;

                case 'most_investors':
                    $mastersQuery->orderByDesc(DB::raw('COALESCE(active_copy_trades_count, 0) + COALESCE(active_pamm_count, 0)'));
                    break;

                default:
                    return response()->json(['error' => 'Invalid filter'], 400);
            }
        }

        // Apply tag filter
        if (!empty($tag)) {
            $tags = explode(',', $tag);

            $mastersQuery->whereIn('roi_period', $tags);
        }

        // Get total count of masters
        $totalRecords = $mastersQuery->count();

        // Fetch paginated results
        $masters = $mastersQuery->paginate($limit);

        return response()->json([
            'masters' => $masters,
            'totalRecords' => $totalRecords,
            'currentPage' => $masters->currentPage(),
        ]);
    }

    public function getMasterSubscriptionPackages(Request $request)
    {
        return MasterSubscriptionPackage::where('master_id', $request->master_id)->get()->map(function ($package) {
            $labelParts = explode('/', $package->label);
            return [
                'value' => $package->id,
                'label' => $labelParts,
                'amount' => $package->amount,
            ];
        });
    }

    public function followPammMaster(JoinPammRequest $request)
    {
        $user = Auth::user();
        $meta_login = $request->meta_login;
        $amount = $request->investment_amount;
        $wallet = Wallet::where('user_id', $user->id)->where('type', 'cash_wallet')->first();
        $masterAccount = Master::with('tradingAccount:id,meta_login,margin_leverage')->find($request->master_id);

        $max_out_amount = null;

        if ($request->amount_package_id) {
            $package = MasterSubscriptionPackage::find($request->amount_package_id);
            $max_out_amount = $package->max_out_amount;
        } else {
            $package = MasterSubscriptionPackage::where('master_id', $masterAccount->id)->first();
        }

        $metaService = new MetaFiveService();

        $connection = $metaService->getConnectionStatus();
        $userTrade = $metaService->userTrade($meta_login);
        $pamm_subscription = PammSubscription::where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Active'])
            ->first();

        // check MT connection
        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        // check open trade
        if ($userTrade) {
            return back()->with('toast', [
                'title' => trans("public.invalid_action"),
                'message' => trans("public.user_got_trade"),
                'type' => 'warning',
            ]);
        }

        // check meta subscriber status
        if ($pamm_subscription) {
            return back()->with('toast', [
                'title' => trans("public.invalid_action"),
                'message' => trans("public.try_again_later"),
                'type' => 'warning',
            ]);
        }

        try {
            $metaService->getUserInfo(TradingAccount::where('meta_login', $meta_login)->get());
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $tradingAccount = TradingAccount::firstWhere('meta_login', $meta_login);

        if ($tradingAccount->margin_leverage != $masterAccount->tradingAccount->margin_leverage) {
            throw ValidationException::withMessages(['meta_login' => 'Leverage not same']);
        }

        if ($tradingAccount->balance + $tradingAccount->credit < $masterAccount->min_join_equity || $tradingAccount->balance + $tradingAccount->credit < ($amount + $masterAccount->subscription_fee) || $tradingAccount->balance + $tradingAccount->credit < $amount) {
            throw ValidationException::withMessages(['meta_login' => trans('public.insufficient_balance')]);
        }

        if ($masterAccount->type == 'ESG' && $tradingAccount->balance < $package->amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        // Calculate the balance from package amount and trading acc balance
        $amount_balance = $tradingAccount->balance - $amount;
        $amount_remain = $amount_balance;

        if ($amount_remain > 0) {
            $deal = [];
            try {
                $deal = $metaService->createDeal($meta_login, $amount_balance, 'Deduct Balance', dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error creating deal: ' . $e->getMessage());
            }

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

        PammSubscription::create([
            'user_id' => $user->id,
            'meta_login' => $meta_login,
            'master_id' => $masterAccount->id,
            'master_meta_login' => $masterAccount->meta_login,
            'subscription_amount' => $masterAccount->type == 'StandardGroup' ? $amount : $amount/2,
            'subscription_package_id' => $request->amount_package_id,
            'subscription_package_product' => $masterAccount->type == 'ESG' ? $amount / 1000 . '棵沉香树' : $request->package_product,
            'type' => $masterAccount->type,
            'strategy_type' => $masterAccount->strategy_type,
            'subscription_number' => RunningNumberService::getID('subscription'),
            'subscription_period' => $masterAccount->join_period,
            'settlement_period' => $masterAccount->roi_period,
            'settlement_date' => now()->addDays($masterAccount->roi_period)->endOfDay(),
            'expired_date' => $masterAccount->join_period > 0 ? now()->addDays($masterAccount->join_period)->endOfDay() : null,
            'max_out_amount' => $max_out_amount,
            'status' => 'Pending',
            'delivery_address' => $request->delivery_address,
        ]);

        $metaService->disableTrade($meta_login);

        return back()->with('toast', [
            'title' => trans("public.success_submission"),
            'message' => trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login,
            'type' => 'success',
        ]);
    }

    public function pamm_subscriptions()
    {
        return Inertia::render('Pamm/PammListing/PammListing', [
            'terms' => Term::where('type', 'pamm_esg')->first(),
            'getTradingAccounts' => (new SelectOptionService())->getTradingAccounts(),
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
        ]);
    }

    public function getPammAccounts(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 6);

        $subQuery = PammSubscription::select(DB::RAW('COALESCE(MIN(CASE WHEN status = "Active" THEN id END),MAX(id)) AS first_id'),
            DB::RAW('SUM(CASE WHEN status = "Active" THEN subscription_amount ELSE 0 END) AS total_amount'),
            DB::RAW('SUM(CASE WHEN status = "Active" THEN CAST(SUBSTRING_INDEX(subscription_package_product, "棵沉香树", 1) AS UNSIGNED) ELSE 0 END) AS package_amount'))
            ->where('user_id', $user->id)
            ->whereNot('status', 'Pending')
            ->groupBy('meta_login', 'master_id');

        $pammQuery = PammSubscription::with([
            'master',
            'master.user',
            'master.tradingUser',
            'master.masterManagementFee',
            'tradingUser:id,name,meta_login',
            'package'
        ])
            ->joinSub($subQuery, 'grouped', function ($join) {
                $join->on('pamm_subscriptions.id', '=', 'grouped.first_id');
            })
            ->where('pamm_subscriptions.user_id', $user->id)
            ->where('pamm_subscriptions.type', $request->type)
            ->whereNot('pamm_subscriptions.status', 'Pending')
            ->orderByRaw("
                CASE
                    WHEN pamm_subscriptions.status = 'Active' THEN 1
                    WHEN pamm_subscriptions.status = 'Terminated' THEN 2
                    WHEN pamm_subscriptions.status = 'Rejected' THEN 3
                    ELSE 4
                END")
            ->latest();

        // Get total count of masters
        $totalRecords = $pammQuery->count();

        // Fetch paginated results
        $subscriptions = $pammQuery->paginate($limit);

        return response()->json([
            'subscriptions' => $subscriptions,
            'totalRecords' => $totalRecords,
            'currentPage' => $subscriptions->currentPage(),
        ]);
    }

    public function joinPamm(Request $request)
    {
        $user = Auth::user();
        $meta_login = $request->meta_login;
        $wallet = Wallet::where('user_id', $user->id)->first();
        $masterAccount = Master::with('tradingAccount:id,meta_login,margin_leverage')->find($request->master_id);
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
        $userTrade = $metaService->userTrade($meta_login);
        $subscriber = Subscriber::where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Subscribing'])
            ->first();

        // check open trade
        if ($userTrade) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.user_got_trade'));
        }

        // check meta subscriber status
        if ($subscriber) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        try {
            $metaService->getUserInfo($user->tradingAccounts);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        $tradingAccount = TradingAccount::where('meta_login', $meta_login)->first();

        if ($tradingAccount->margin_leverage != $masterAccount->tradingAccount->margin_leverage) {
            throw ValidationException::withMessages(['meta_login' => 'Leverage not same']);
        }

        if ($tradingAccount->balance < $masterAccount->min_join_equity || $tradingAccount->balance < $masterAccount->subscription_fee) {
            throw ValidationException::withMessages(['meta_login' => trans('public.insufficient_balance')]);
        }

        // Calculate the remainder of the balance when divided by 100, including cents
        $remainder = fmod($tradingAccount->balance, 100);

        // Format the remainder to ensure it represents the cents accurately
        $amount = number_format($remainder, 2, '.', '');
        $amount = floatval($amount);

        if ($amount > 0) {
            $deal = [];
            try {
                $deal = $metaService->createDeal($tradingAccount->meta_login, $amount, 'Withdraw from trading account', dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error creating deal: '. $e->getMessage());
            }

            // Calculate new wallet amount
            $new_wallet_amount = $wallet->balance + $amount;

            // Create transaction
            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'to_wallet_id' => $wallet->id,
                'from_meta_login' => $tradingAccount->meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'BalanceOut',
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

            try {
                $metaService->getUserInfo($user->tradingAccounts);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            $tradingAccount = TradingAccount::where('meta_login', $meta_login)->first();
        }

        $subscriberData = [];
        if ($masterAccount->subscription_fee > 0) {
            $transaction_number = RunningNumberService::getID('transaction');

            $transaction = Transaction::create([
                'category' => 'wallet',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
                'transaction_number' => $transaction_number,
                'transaction_type' => 'SubscriptionFee',
                'amount' => $masterAccount->subscription_fee,
                'transaction_charges' => 0,
                'transaction_amount' => $masterAccount->subscription_fee,
                'status' => 'Processing',
            ]);

            // Create diff subscriptions data
            $subscriberData = [
                'initial_subscription_fee' => $masterAccount->subscription_fee,
                'transaction_id' => $transaction->id,
            ];
        }

        // change to subscriber
        Subscriber::create($subscriberData + [
                'user_id' => $user->id,
                'trading_account_id' => $tradingAccount->id,
                'meta_login' => $meta_login,
                'initial_meta_balance' => $tradingAccount->balance,
                'master_id' => $masterAccount->id,
                'master_meta_login' => $masterAccount->meta_login,
                'roi_period' => $masterAccount->roi_period,
                'subscribe_amount' => $tradingAccount->balance,
                'status' => 'Pending'
            ]);

        $metaService->disableTrade($meta_login);

        if ($amount > 0) {
            return redirect()->back()
                ->with('title', trans('public.success_subscribe'))
                ->with('success', trans('public.successfully_subscribe_with_remainder', ['amount' => $amount, 'balance' => $tradingAccount->balance]). ' ' . trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login);
        } else {
            return redirect()->back()
                ->with('title', trans('public.success_subscribe'))
                ->with('success', trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login);
        }
    }

    public function getPammSubscriptions(Request $request)
    {
        $query = PammSubscription::with([
            'master',
            'master.tradingUser',
            'master.masterManagementFee'
        ])
            ->where('user_id', Auth::id())
            ->where('meta_login', $request->meta_login);

        $join_start_date = $request->query('joinStartDate');
        $join_end_date = $request->query('joinEndDate');

        if ($join_start_date && $join_end_date) {
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $join_start_date)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $join_end_date)->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($request->filled('master_id')) {
            $master_id = $request->input('master_id');
            $query->whereHas('master', function ($q) use ($master_id) {
                $q->where('id', $master_id);
            });
        } else {
            $query->whereHas('master', function ($q) use ($query) {
                $q->where('id', $query->latest()->first()->master_id);
            });
        }

        $results = $query
            ->orderByDesc('approval_date')
            ->get()
            ->map(function ($item) {
                $activeSubscriptions = SubscriptionBatch::where('meta_login', $item->meta_login)->where('status', 'Active')->count();

                $item->terminateBadgeStatus = $activeSubscriptions > 1;

                return $item;
            });

        return response()->json($results);
    }

    public function getPammSubscriptionDetail(Request $request)
    {
        $pamm = PammSubscription::where('id', $request->subscription_id)->first();
        return response()->json($pamm);
    }

    public function topUpPamm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'top_up_amount' => ['required', 'numeric'],
            'terms' => ['accepted'],
        ])->setAttributeNames([
            'top_up_amount' => trans('public.top_up_amount'),
            'terms' => trans('public.terms_and_conditions'),
        ]);
        $validator->validate();

        $wallet = Wallet::find($request->wallet_id);
        $user = Auth::user();
        $pamm = PammSubscription::find($request->pamm_id);
        $meta_login = $pamm->meta_login;
        $metaService = new MetaFiveService();
        $cash_wallet = Wallet::where('user_id', $user->id)->where('type', 'cash_wallet')->first();
        $amount = $request->top_up_amount;
        $masterAccount = Master::find($pamm->master_id);
        $connection = $metaService->getConnectionStatus();

        // check MT connection
        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        try {
            $metaService->getUserInfo($user->tradingAccounts);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

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
            throw ValidationException::withMessages(['top_up_amount' => trans('public.e_wallet_amount_error', ['SumAmount' => $eWalletAmount + $cashWalletAmount, 'DepositAmount' => $amount])]);
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $eWalletAmount)) {
            throw ValidationException::withMessages(['eWalletAmount' => trans('public.invalid_e_wallet_amount')]);
        }

        if ($wallet->type == 'e_wallet') {
            if ($wallet->balance < $eWalletAmount || $amount <= 0) {
                throw ValidationException::withMessages(['top_up_amount' => trans('public.insufficient_wallet_balance', ['wallet' => trans('public.' . $wallet->type)])]);
            }
            if ($cash_wallet->balance < $cashWalletAmount) {
                throw ValidationException::withMessages(['top_up_amount' => trans('public.insufficient_wallet_balance', ['wallet' => trans('public.' . $cash_wallet->type)])]);
            }
        } elseif ($wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['top_up_amount' => trans('public.insufficient_wallet_balance', ['wallet' => trans('public.' . $wallet->type)])]);
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
            $this->createTransaction($user->id, $wallet->id, $meta_login, $deal['deal_Id'], 'TopUp', $eWalletAmount, $wallet->balance - $eWalletAmount, $deal['conduct_Deal']['comment'], 0, 'trading_account');
            $wallet->balance -= $eWalletAmount;
            $wallet->save();

            $this->createTransaction($user->id, $cash_wallet->id, $meta_login, $deal['deal_Id'], 'TopUp', $cashWalletAmount, $cash_wallet->balance - $cashWalletAmount, $deal['conduct_Deal']['comment'], 0, 'trading_account');
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
                'transaction_type' => 'TopUp',
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
                'new_wallet_amount' => $wallet->balance - $amount,
            ]);

            $wallet->update(['balance' => $wallet->balance - $amount]);
        }

        $pamm_subscription = PammSubscription::create([
            'user_id' => $user->id,
            'meta_login' => $meta_login,
            'master_id' => $masterAccount->id,
            'master_meta_login' => $masterAccount->meta_login,
            'subscription_amount' => $masterAccount->type == 'ESG' ? $amount/2 : $amount,
            'subscription_package_product' => $masterAccount->type == 'ESG' ? $request->top_up_amount / 1000 . '棵沉香树' : null,
            'type' => $masterAccount->type,
            'strategy_type' => $masterAccount->strategy_type,
            'subscription_number' => RunningNumberService::getID('subscription'),
            'subscription_period' => $masterAccount->join_period,
            'settlement_period' => $masterAccount->roi_period,
            'settlement_date' => now()->addDays($masterAccount->roi_period)->endOfDay(),
            'expired_date' => $masterAccount->join_period > 0 ? now()->addDays($masterAccount->join_period)->endOfDay() : null,
            'approval_date' => now(),
            'status' => 'Active',
            'remarks' => 'Top Up'
        ]);

        // balance half from trading account for ESG
        if ($masterAccount->type == 'ESG') {
            $client_deal = [];

            try {
                $client_deal = (new MetaFiveService())->createDeal($pamm_subscription->meta_login, $pamm_subscription->subscription_amount, '#' . $pamm_subscription->meta_login, dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }

            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $pamm_subscription->master->user_id,
                'from_meta_login' => $pamm_subscription->meta_login,
                'ticket' => $client_deal['deal_Id'],
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'PurchaseProduct',
                'fund_type' => 'RealFund',
                'amount' => $pamm_subscription->subscription_amount,
                'transaction_charges' => 0,
                'transaction_amount' => $pamm_subscription->subscription_amount,
                'status' => 'Success',
                'comment' => $client_deal['conduct_Deal']['comment'],
            ]);
        }

        // fund to master
        $description = $pamm_subscription->meta_login ? 'Login #' . $pamm_subscription->meta_login : ('Client #' . $pamm_subscription->user_id);
        $master_deal = [];

        try {
            $master_deal = (new MetaFiveService())->createDeal($pamm_subscription->master_meta_login, $pamm_subscription->subscription_amount, $description, dealAction::DEPOSIT);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        Transaction::create([
            'category' => 'trading_account',
            'user_id' => $pamm_subscription->master->user_id,
            'to_meta_login' => $pamm_subscription->master_meta_login,
            'ticket' => $master_deal['deal_Id'],
            'transaction_number' => RunningNumberService::getID('transaction'),
            'transaction_type' => 'DepositCapital',
            'fund_type' => 'RealFund',
            'amount' => $pamm_subscription->subscription_amount,
            'transaction_charges' => 0,
            'transaction_amount' => $pamm_subscription->subscription_amount,
            'status' => 'Success',
            'comment' => $master_deal['conduct_Deal']['comment'],
        ]);

        $masterAccount->total_fund += $pamm_subscription->subscription_amount;
        $masterAccount->save();

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.toast_success_top_up_message'),
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

    public function revokePamm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terms' => ['accepted']
        ]);
        $validator->setAttributeNames([
            'terms' => trans('public.terms_and_conditions'),
        ]);
        $validator->validate();

        $pamm_batches = PammSubscription::where('meta_login', $request->meta_login)
            ->where('master_meta_login', $request->master_meta_login)
            ->where('status', 'Active')
            ->get();

        foreach ($pamm_batches as $pamm_batch) {
            if ($pamm_batch->status == 'Terminated') {
                return redirect()->back()
                    ->with('title', trans('public.terminated_subscription'))
                    ->with('warning', trans('public.terminated_subscription_error'));
            }

            $pamm_batch->update([
                'termination_date' => now(),
                'status' => 'Terminated'
            ]);
        }

        return redirect()->back()
            ->with('title', trans('public.success_revoke'))
            ->with('success', trans('public.successfully_revoked_pamm'));
    }

    public function terminatePammBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terms' => ['accepted']
        ])->setAttributeNames([
            'terms' => trans('public.terms_and_conditions'),
        ]);
        $validator->validate();

        $subscription_batch = PammSubscription::find($request->id);

        if ($subscription_batch->status == 'Terminated') {
            return redirect()->back()
                ->with('title', trans('public.terminated'))
                ->with('warning', trans('public.terminated_subscription_error'));
        }

        $total_batch_amount = PammSubscription::where('meta_login', $subscription_batch->meta_login)
            ->where('status', 'Active')
            ->sum('subscription_amount');

        $master = Master::find($subscription_batch->master_id);

        if ($total_batch_amount - $subscription_batch->subscription_amount < $master->min_join_equity) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.low_subscription_amount_warning', ['amount' => $master->min_join_equity]));
        }

        $subscription_batch->update([
            'termination_date' => now(),
            'status' => 'Terminated'
        ]);

        return redirect()->back()
            ->with('title', trans('public.success_terminate'))
            ->with('success', trans('public.successfully_terminate'). ': ' . $subscription_batch->subscription_number);
    }
}
