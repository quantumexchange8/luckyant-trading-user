<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Models\Master;
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
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CopyTradingController extends Controller
{
    public function index(Request $request)
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
            'category' => 'copy_trade',
            'strategy_type' => $strategyType,
            'status' => 'Active',
        ]);

        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        $masterQuery->whereHas('visible_to_leaders', function ($q) use ($first_leader) {
            $q->where('user_id', $first_leader->id);
        });

        $mastersCount = $masterQuery->count();

        return Inertia::render('CopyTrading/MasterListing', [
            'mastersCount' => $mastersCount,
            'strategyType' => strtolower($strategyType),
        ]);
    }

    public function getMasters(Request $request)
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
                'tradingUser:id,meta_login,name,company,account_type',
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
                'category' => 'copy_trade',
                'strategy_type' => $strategyType,
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

    public function getAvailableAccounts(Request $request)
    {
        $user = Auth::user();

        $available_accounts = $user->tradingAccounts()
            ->whereDoesntHave('subscriber', function ($query) {
                $query->whereIn('status', ['Subscribing', 'Pending']);
            })->whereDoesntHave('pamm_subscription', function ($query) {
                $query->whereIn('status', ['Active', 'Pending']);
            })
            ->where('account_type', $request->account_type)
            ->whereNot('meta_login', $request->master_login)
            ->select([
                'id',
                'meta_login',
                'balance',
                'account_type'
            ])
            ->get();

        $connection = (new MetaFiveService())->getConnectionStatus();

        if ($connection == 0) {
            if ($available_accounts->isNotEmpty()) {
                try {
                    (new MetaFiveService())->getUserInfo($available_accounts);
                } catch (\Exception $e) {
                    \Log::error('Error update trading accounts: '. $e->getMessage());
                }
            }
        } else {
            return back()->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans("public.toast_warning_meta_service_message"),
                'type' => 'warning',
            ]);
        }

        return response()->json($available_accounts);
    }

    public function getTerms(Request $request)
    {
        $type = $request->input('type', 'subscribe');
        $terms = Term::where('type', $type)->get();

        $structuredTerms = [];

        foreach ($terms as $term) {
//            $locale = $term->locale;
            $structuredTerms['en'] = [
                'title' => $term->title,
                'contents' => $term->contents,
            ];
        }

        return response()->json($structuredTerms);
    }

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

    public function joinCopyTrade(SubscribeRequest $request)
    {
        $user = Auth::user();
        $meta_login = $request->meta_login;
        $type = $request->type;
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
            return back()->with('toast', [
                'title' => trans("public.invalid_action"),
                'message' => trans("public.user_got_trade"),
                'type' => 'warning',
            ]);
        }

        // check meta subscriber status
        if ($subscriber) {
            return back()->with('toast', [
                'title' => trans("public.invalid_action"),
                'message' => trans("public.try_again_later"),
                'type' => 'warning',
            ]);
        }

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans("public.server_under_maintenance"),
                'message' => trans("public.try_again_later"),
                'type' => 'warning',
            ]);
        }

        if($type != 2) {
            try {
                $metaService->getUserInfo(TradingAccount::where('meta_login', $meta_login)->get());
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }
        }

        $tradingAccount = TradingAccount::firstWhere('meta_login', $meta_login);

        if ($tradingAccount->margin_leverage != $masterAccount->tradingAccount->margin_leverage) {
            throw ValidationException::withMessages(['meta_login' => trans('leverage_not_same')]);
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

            if ($type != 2) {
                try {
                    $deal = $metaService->createDeal($tradingAccount->meta_login, $amount, 'Withdraw', dealAction::WITHDRAW);
                } catch (\Exception $e) {
                    \Log::error('Error creating deal: '. $e->getMessage());
                }
            }
            else {
                $tradingAccount->update(['balance' => $tradingAccount->balance - $amount]);
            }

            $dealId = $deal['deal_Id'] ?? null;
            $comment = $deal['conduct_Deal']['comment'] ?? 'Withdraw';

            // Calculate new wallet amount
            $new_wallet_amount = $wallet->balance + $amount;

            // Create transaction
            Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'to_wallet_id' => $wallet->id,
                'from_meta_login' => $tradingAccount->meta_login,
                'ticket' => $dealId,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'BalanceOut',
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'Success',
                'comment' => $comment,
                'new_wallet_amount' => $new_wallet_amount,
            ]);

            $wallet->update([
                'balance' => $new_wallet_amount
            ]);

            $tradingAccount = TradingAccount::firstWhere('meta_login', $meta_login);
        }

        $subscriberData = [];
        if ($masterAccount->subscription_fee > 0) {
            $transaction = Transaction::create([
                'category' => 'wallet',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
                'transaction_number' => RunningNumberService::getID('transaction'),
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

        if ($type != 2) {
            $metaService->disableTrade($meta_login);
        }

        if ($amount > 0) {
            return back()->with('toast', [
                'title' => trans("public.success_submission"),
                'message' => trans('public.successfully_subscribe_with_remainder', ['amount' => $amount, 'balance' => $tradingAccount->balance]). ' ' . trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login,
                'type' => 'success',
            ]);
        } else {
            return back()->with('toast', [
                'title' => trans("public.success_submission"),
                'message' => trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login,
                'type' => 'success',
            ]);
        }
    }

    public function subscriptions(Request $request)
    {
        $strategyType = $this->getStrategyType($request);

        $copyTradesCount = Subscriber::where('user_id', Auth::id())
            ->whereHas('subscription', function ($query) use ($strategyType) {
                $query->where('strategy_type', $strategyType);
            })
            ->count();

        $pammsCount = PammSubscription::where('user_id', Auth::id())
            ->where('type', 'StandardGroup')
            ->where('strategy_type', $strategyType)
            ->distinct('meta_login', 'master_id')
            ->count();

        $esgsCount = PammSubscription::where('user_id', Auth::id())
            ->where('type', 'ESG')
            ->where('strategy_type', $strategyType)
            ->distinct('meta_login', 'master_id')
            ->count();

        return Inertia::render('Trading/TradingSubscriptions', [
            'strategyType' => strtolower($strategyType),
            'copyTradesCount' => $copyTradesCount,
            'pammsCount' => $pammsCount,
            'esgsCount' => $esgsCount,
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
        ]);
    }

    public function getCopyTradeAccounts(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 6);

        $subscriberQuery = Subscriber::query()
            ->with([
                'tradingUser:id,meta_login,name,company,account_type',
                'master:id,meta_login,category,type,strategy_type,estimated_monthly_returns,sharing_profit,max_drawdown',
                'master.masterManagementFee',
                'master.tradingUser:id,meta_login,name,company,account_type',
                'subscription'
            ])
            ->where('user_id', $user->id)
            ->whereNot('status', 'Pending')
            ->orderByRaw("
                CASE
                    WHEN status = 'Subscribing' THEN 1
                    WHEN status = 'Pending' THEN 2
                    WHEN status = 'Expiring' THEN 3
                    WHEN status = 'Switched' THEN 4
                    WHEN status = 'Unsubscribed' THEN 5
                    WHEN status = 'Rejected' THEN 6
                    ELSE 7
                END")
            ->latest();

        // Get total count of masters
        $totalRecords = $subscriberQuery->count();

        // Fetch paginated results
        $subscriptions = $subscriberQuery->paginate($limit);

        return response()->json([
            'subscriptions' => $subscriptions,
            'totalRecords' => $totalRecords,
            'currentPage' => $subscriptions->currentPage(),
        ]);
    }

    public function getNewMaster(Request $request)
    {
        $strategyType = $this->getStrategyType($request);
        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        $masters = Master::with([
            'tradingUser:id,name,company,meta_login',
        ])
            ->whereNot('id', $request->current_master_id)
            ->where('strategy_type', $strategyType)
            ->where('category', 'copy_trade')
            ->where('status', 'Active')
            ->whereHas('visible_to_leaders', function ($q) use ($first_leader) {
                $q->where('user_id', $first_leader->id);
            })
            ->get();

        return response()->json($masters);
    }

    public function getCopyTradeSubscriptions(Request $request)
    {
        $query = SubscriptionBatch::with([
            'master',
            'master.tradingUser',
            'master.masterManagementFee'
        ])
            ->where('user_id', Auth::id())
            ->where('meta_login', $request->meta_login);

        $join_start_date = $request->query('joinStartDate');
        $join_end_date = $request->query('joinEndDate');

        if ($join_start_date && $join_end_date) {
            $start_date = Carbon::createFromFormat('Y-m-d', $join_start_date)->startOfDay();
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
}
