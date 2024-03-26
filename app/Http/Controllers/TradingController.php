<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Models\CopyTradeHistory;
use App\Models\Master;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionRenewalRequest;
use App\Models\Term;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TradingController extends Controller
{
    public function master_listing()
    {
        return Inertia::render('Trading/MasterListing', [
            'terms' => Term::where('type', 'subscribe')->first()
        ]);
    }

    public function getMasterAccounts(Request $request)
    {
        $masterAccounts = Master::with(['user:id,username,name,email', 'tradingAccount:id,meta_login,balance,equity', 'tradingUser:id,name,company'])
            ->where('status', 'Active')
            ->where('signal_status', 1)
            ->whereNot('user_id', \Auth::id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->whereHas('tradingAccount', function ($q) use ($search) {
                    $q->where('meta_login', 'like', $search);
                })
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', $search)
                            ->orWhere('username', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                switch ($type) {
                    case 'max_equity':
                        $query->orderByDesc('min_join_equity');
                        break;
                    case 'min_equity':
                        $query->orderBy('min_join_equity');
                        break;
                    case 'max_sub':
                        $query->withCount('subscribers')->orderByDesc('subscribers_count');
                        break;
                    case 'min_sub':
                        $query->withCount('subscribers')->orderBy('subscribers_count');
                        break;
                    // Add more cases as needed for other 'type' values
                }
            })
            ->latest()
            ->paginate(10);

        $masterAccounts->each(function ($master) {
            $totalSubscriptionsFee = Subscription::where('master_id', $master->id)->where('status', 'Active')->sum('meta_balance');

            $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
//            $master->subscribersCount = $master->subscribers->count();
            $master->totalFundWidth = (($totalSubscriptionsFee + $master->extra_fund) / $master->total_fund) * 100;
        });

        return response()->json($masterAccounts);
    }

    public function subscribeMaster(SubscribeRequest $request)
    {
        $user = Auth::user();
        $meta_login = $request->meta_login;
        $wallet = Wallet::where('user_id', $user->id)->first();
        $masterAccount = Master::with('tradingAccount:id,meta_login,margin_leverage')->find($request->master_id);
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
        $subscriber = Subscriber::where('meta_login', $meta_login)
            ->whereIn('status', ['Pending', 'Subscribing'])
            ->first();

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
        }

        $tradingAccount = TradingAccount::where('meta_login', $meta_login)->first();

        if ($tradingAccount->margin_leverage != $masterAccount->tradingAccount->margin_leverage) {
            throw ValidationException::withMessages(['meta_login' => 'Leverage not same']);
        }

        if ($tradingAccount->equity < $masterAccount->min_join_equity || $tradingAccount->equity < $masterAccount->subscription_fee) {
            throw ValidationException::withMessages(['meta_login' => trans('public.insufficient_balance')]);
        }

        if ($masterAccount->subscription_fee > 0) {
            $transaction_number = RunningNumberService::getID('transaction');

            $transaction = Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
//                'ticket' => $deal['deal_Id'],
                'transaction_number' => $transaction_number,
                'transaction_type' => 'SubscriptionFee',
                'amount' => $masterAccount->subscription_fee,
                'transaction_charges' => 0,
                'transaction_amount' => $masterAccount->subscription_fee,
                'status' => 'Processing',
//                'comment' => $deal['conduct_Deal']['comment'],
            ]);

            // Create diff subscriptions data
            $subscriptionData = [
                'user_id' => $user->id,
                'trading_account_id' => $tradingAccount->id,
                'meta_login' => $meta_login,
                'meta_balance' => $tradingAccount->balance,
                'transaction_id' => $transaction->id,
            ];
        } else {
            $subscriptionData = [
                'user_id' => $user->id,
                'trading_account_id' => $tradingAccount->id,
                'meta_login' => $meta_login,
                'meta_balance' => $tradingAccount->balance,
            ];
        }

        $subscription_number = RunningNumberService::getID('subscription');

        $subscription = Subscription::create($subscriptionData + [
            'master_id' => $masterAccount->id,
            'subscription_number' => $subscription_number,
            'subscription_period' => $masterAccount->roi_period,
            'subscription_fee' => $masterAccount->subscription_fee,
            'status' => 'Pending'
        ]);

        Subscriber::create([
            'user_id' => $user->id,
            'trading_account_id' => $tradingAccount->id,
            'meta_login' => $meta_login,
            'master_id' => $masterAccount->id,
            'master_meta_login' => $masterAccount->meta_login,
            'subscription_id' => $subscription->id,
            'status' => 'Pending'
        ]);

        $metaService->disableTrade($meta_login);

        return redirect()->back()
            ->with('title', trans('public.success_subscribe'))
            ->with('success', trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login);
    }

    public function getSubscriptions(Request $request)
    {
        $masterAccounts = Subscriber::with(['user:id,username,name,email', 'tradingAccount:id,meta_login,balance,equity', 'master', 'master.tradingUser','subscription'])
            ->where('user_id', Auth::id())
            ->where('status', 'Subscribing')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->whereHas('master', function ($q) use ($search) {
                    $q->where('meta_login', 'like', $search);
                })
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                switch ($type) {
                    case 'max_equity':
                        $query->orderByDesc('min_join_equity');
                        break;
                    case 'min_equity':
                        $query->orderBy('min_join_equity');
                        break;
                    case 'max_sub':
                        $query->withCount('subscribers')->orderByDesc('subscribers_count');
                        break;
                    case 'min_sub':
                        $query->withCount('subscribers')->orderBy('subscribers_count');
                        break;
                    // Add more cases as needed for other 'type' values
                }
            })
            ->latest()
            ->paginate(10);

        $masterAccounts->each(function ($subscriber) {
            $approvalDate = Carbon::parse($subscriber->subscription->approval_date);
            $today = Carbon::today();
            $interval = $today->diff($approvalDate);

            // Get the total number of days from the interval
            $join_days = $approvalDate->diffInDays($today);

            $subscriber->master->user->profile_photo = $subscriber->master->user->getFirstMediaUrl('profile_photo');
            $subscriber->join_days = $join_days;
        });

        return response()->json($masterAccounts);
    }

    public function masterListingDetail($id)
    {
        $master = Master::with(['user:id,username,name,email', 'tradingAccount:id,meta_login,balance,equity', 'tradingUser:id,name,meta_login,company'])->find($id);

        if (!$master || $master->status !== 'Active' || $master->user_id === auth()->id()) {
            return redirect()->route('trading.master_listing')
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        $totalSubscriptionsFee = Subscription::where('master_id', $master->id)
            ->sum('meta_balance');

        $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
//        $master->subscribersCount = $master->subscribers->count();
        $master->totalFundWidth = (($totalSubscriptionsFee + $master->extra_fund) / $master->total_fund) * 100;

        return Inertia::render('Trading/MasterListing/MasterListingDetail', [
            'masterListingDetail' => $master,
        ]);
    }

    public function terminateSubscription(Request $request)
    {
        $subscription = Subscription::find($request->subscription_id);
        $subscriber = Subscriber::where('subscription_id', $subscription->id)->first();

        if ($subscription->status == 'Terminated') {
            return redirect()->back()
                ->with('title', trans('public.terminated_subscription'))
                ->with('warning', trans('public.terminated_subscription_error'));
        }

        $subscription->update([
            'termination_date' => now(),
            'status' => 'Terminated',
            'auto_renewal' => false,
        ]);

        $subscriber->update([
            'unsubscribe_date' => now(),
            'status' => 'Unsubscribed'
        ]);

        return redirect()->back()
            ->with('title', trans('public.success_terminate'))
            ->with('success', trans('public.successfully_terminate'). ': ' . $subscription->subscription_number);
    }

    public function renewalSubscription(Request $request)
    {
        $subscription = Subscription::find($request->subscription_id);

        if ($subscription->status == 'Terminated') {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        $renewRequest = SubscriptionRenewalRequest::where('subscription_id', $subscription->id)
            ->where('status', 'Pending')
            ->latest()
            ->first();

        if ($renewRequest) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        $messages = [
            'stop_renewal' => [
                'title' => trans('public.success_stop_renewal'),
                'success' => trans('public.successfully_stop_auto_renew'),
            ],
            'request_auto_renewal' => [
                'title' => trans('public.success_request_renewal'),
                'success' => trans('public.successfully_request_renewal'),
            ],
        ];

        if (array_key_exists($request->action, $messages)) {
            if ($request->action == 'stop_renewal') {
                $subscription->update([
                    'auto_renewal' => false,
                ]);
            } elseif ($request->action == 'request_auto_renewal') {
                SubscriptionRenewalRequest::create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                ]);
            }

            return redirect()->back()
                ->with('title', $messages[$request->action]['title'])
                ->with('success', $messages[$request->action]['success']);
        }

        return redirect()->back()
            ->with('title', trans('public.invalid_action'))
            ->with('warning', trans('public.try_again_later'));
    }

    public function getSubscriptionHistories(Request $request)
    {
        $subscriptionHistories = Subscription::with(['user:id,name,email', 'tradingAccount:id,meta_login,balance,equity', 'master', 'master.tradingUser'])
            ->where('user_id', Auth::id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->where('meta_login','like', $search);
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $query->where('status', $type);
            })
            ->latest()
            ->paginate(10);

        $subscriptionHistories->each(function ($subscriber) {
            $subscriber->master->user->profile_photo = $subscriber->master->user->getFirstMediaUrl('profile_photo');
        });

        return response()->json($subscriptionHistories);
    }

    public function getMasterTradeChart($meta_login)
    {
        $topFiveSymbols = CopyTradeHistory::select('symbol', \DB::raw('COUNT(*) as symbol_count'))
            ->where('meta_login', $meta_login)
            ->where('status', 'closed')
            ->groupBy('symbol')
            ->orderByDesc('symbol_count')
            ->limit(5)
            ->get();

        $walletColors = ['#003f5c', '#58508d', '#bc5090', '#ff6361', '#ffa600'];

        $chartData = [
            'labels' => $topFiveSymbols->pluck('symbol'),
            'datasets' => [],
        ];

        $symbolCount = [];
        $backgroundColors = [];
        $colorIndex = 0;

        foreach ($topFiveSymbols as $symbol) {
            $symbolCount[] = $symbol->symbol_count;

            // Get color from the array based on the color index
            $backgroundColor = $walletColors[$colorIndex % count($walletColors)];

            $backgroundColors[] = $backgroundColor;

            // Increment color index
            $colorIndex++;
        }

        $dataset = [
            'data' => $symbolCount,
            'backgroundColor' => $backgroundColors,
            'offset' => 5,
            'borderColor' => 'transparent'
        ];

        $chartData['datasets'][] = $dataset;

        return response()->json($chartData);
    }

    public function getTradeHistories(Request $request, $meta_login)
    {
        $tradeHistories = CopyTradeHistory::where('meta_login', $meta_login)
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $types = explode(',', $type); // Convert comma-separated string to array
                $query->whereIn('symbol', $types);
            })
            ->when($request->filled('tradeType'), function ($query) use ($request) {
                $tradeType = $request->input('tradeType');
                $query->where('trade_type', $tradeType);
            })
            ->where('status', 'closed')
            ->orderByDesc('time_close')
            ->paginate(10);

        return response()->json($tradeHistories);
    }

    public function getTradingSymbols(Request $request)
    {
        $symbols = CopyTradeHistory::query()
            ->where('meta_login', $request->meta_login)
            ->where('status', 'closed')
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('symbol', 'like', "%{$search}%");
                });
            })
            ->select('symbol')
            ->distinct()
            ->get();

        return response()->json($symbols);
    }
}
