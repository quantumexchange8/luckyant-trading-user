<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeRequest;
use App\Models\CopyTradeHistory;
use App\Models\CopyTradeTransaction;
use App\Models\Master;
use App\Models\MasterManagementFee;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\SubscriptionPenaltyLog;
use App\Models\SubscriptionRenewalRequest;
use App\Models\SwitchMaster;
use App\Models\Term;
use App\Models\TradeHistory;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
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
        $user = Auth::user();
        $first_leader = $user->getFirstLeader();

        $masterAccounts = Master::with([
            'user:id,username,name,email',
            'tradingAccount:id,meta_login,balance,equity',
            'tradingUser:id,name,company',
            'masterManagementFee'
        ])
            ->where('status', 'Active')
            ->where('signal_status', 1)
            ->whereNot('user_id', $user->id)
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
            });

        if ($user->is_public == 0 && $first_leader) {
            $leader = $first_leader;
            while ($leader && $leader->masterAccounts->isEmpty()) {
                $leader = $leader->getFirstLeader();
            }

            if ($leader) {
                $masterAccounts = $masterAccounts
                    ->where('is_public', $leader->is_public)
                    ->whereIn('user_id', $leader->masterAccounts->pluck('user_id'));
            } else {
                // If leader is null, reset $masterAccounts to an empty query
                $masterAccounts = $masterAccounts->where('id', null);
            }
        } elseif ($user->is_public == 1 && $first_leader) {
            $masterAccounts->where('is_public', $first_leader->is_public);
        } else {
            if ($user->is_public == 0) {
                $masterAccounts->where('is_public', $user->is_public)
                    ->whereIn('id', $user->masterAccounts->pluck('id'));
            } else {
                $masterAccounts->where('is_public', $user->is_public);
            }
        }

        $masterAccounts = $masterAccounts->latest()->paginate(10);

        $masterAccounts->each(function ($master) {
            $totalSubscriptionsFee = Subscription::where('master_id', $master->id)
                ->where('status', 'Active')
                ->sum('meta_balance');

            $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
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

    public function getSubscriptions(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'approval_date';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = SubscriptionBatch::with(['master', 'master.tradingUser', 'master.masterManagementFee'])
            ->where('user_id', Auth::id())
            ->where('meta_login', $request->meta_login);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('master.tradingUser', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('meta_login', 'like', $search)
                        ->orWhere('company', 'like', $search);
                });
            })
                ->orWhere('meta_login', 'like', $search);
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('approval_date', [$start_date, $end_date]);
        }

        if ($request->filled('master')) {
            $master = $request->input('master');
            $query->whereHas('master', function ($q) use ($master) {
                $q->where('id', $master);
            });
        } else {
            $query->whereHas('master', function ($q) use ($query) {
                $q->where('id', $query->latest()->first()->master_id);
            });
        }

        $results = $query
            ->orderBy($column == null ? 'approval_date' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $results->each(function ($batch) {
            $approvalDate = Carbon::parse($batch->approval_date);
            $today = Carbon::today();
            $join_days = $approvalDate->diffInDays($batch->status == 'Terminated' ? $batch->termination_date : $today);

            $management_fee = MasterManagementFee::where('master_id', $batch->master_id)
                ->where('penalty_days', '>', $join_days)
                ->first();

            $batch->join_days = $join_days;
            $batch->management_period = $batch->master->masterManagementFee->sum('penalty_days');
            $batch->management_fee = $management_fee->penalty_percentage;
        });

        return response()->json($results);
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
        $validator = Validator::make($request->all(), [
            'terms' => ['accepted']
        ]);
        $validator->setAttributeNames([
            'terms' => trans('public.terms_and_conditions'),
        ]);

        $subscription = Subscription::find($request->subscription_id);

        if ($subscription->status == 'Terminated') {
            return redirect()->back()
                ->with('title', trans('public.terminated_subscription'))
                ->with('warning', trans('public.terminated_subscription_error'));
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            Subscriber::where('subscription_id', $subscription->id)->first()->update([
                'unsubscribe_date' => now(),
                'status' => 'Unsubscribed',
                'auto_renewal' => false
            ]);

            $subscription->update([
                'termination_date' => now(),
                'status' => 'Terminated',
                'auto_renewal' => false,
            ]);

            $subscription_batches = SubscriptionBatch::where('subscription_id', $subscription->id)
                ->where('status', 'Active')
                ->get();

            foreach ($subscription_batches as $subscription_batch) {
                $subscription_batch->update([
                    'auto_renewal' => false,
                    'termination_date' => now(),
                    'status' => 'Terminated'
                ]);
            }

            return redirect()->back()
                ->with('title', trans('public.success_terminate'))
                ->with('success', trans('public.successfully_terminate'). ': ' . $subscription->subscription_number);
        }
    }

    public function renewalSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terms' => ['accepted']
        ]);
        $validator->setAttributeNames([
            'terms' => trans('public.terms_and_conditions'),
        ]);

        $subscription = Subscription::find($request->subscription_id);

        if ($subscription->status == 'Terminated') {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
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
                    Subscriber::where('subscription_id', $subscription->id)->first()->update([
                        'status' => 'Expiring',
                        'auto_renewal' => false
                    ]);

                    $subscription->update([
                        'auto_renewal' => false,
                    ]);

                    $subscription_batches = SubscriptionBatch::where('subscription_id', $subscription->id)
                        ->where('status', 'Active')
                        ->get();

                    foreach ($subscription_batches as $subscription_batch) {
                        $subscription_batch->update([
                            'auto_renewal' => false,
                            'status' => 'Expiring'
                        ]);
                    }
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
    }

    public function getSubscriptionHistories(Request $request)
    {
        $subscriptionHistories = Subscription::with(['user:id,name,email', 'tradingAccount:id,meta_login,balance,equity', 'master', 'master.tradingUser'])
            ->where('user_id', Auth::id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->where(function ($q) use ($search) {
                    $q->whereHas('master.tradingUser', function ($user) use ($search) {
                        $user->where('name', 'like', $search)
                            ->orWhere('meta_login', 'like', $search)
                            ->orWhere('company', 'like', $search);
                    });
                })
                    ->orWhere('meta_login', 'like', $search);
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
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'time_close';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $tradeHistories = TradeHistory::where('meta_login', $meta_login)
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('time_close', [$start_date, $end_date]);
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $types = explode(',', $type); // Convert comma-separated string to array
                $query->whereIn('symbol', $types);
            })
            ->when($request->filled('tradeType'), function ($query) use ($request) {
                $tradeType = $request->input('tradeType');
                $query->where('trade_type', $tradeType);
            });

            $tradeHistories = $tradeHistories->where('trade_status', 'Closed')
                ->orderBy($column, $sortOrder)
                ->paginate($request->input('paginate', 10));

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

    public function subscription_listing()
    {
        $subscribed_master_id = Subscriber::where('user_id', Auth::id())
            ->where('status', 'Subscribing')
            ->get()
            ->pluck('master_id');

        return Inertia::render('Trading/SubscriptionListing/SubscriptionListing', [
            'terms' => Term::where('type', 'subscribe')->first(),
        ]);
    }

    public function subscription_history()
    {
        return Inertia::render('Trading/SubscriptionListing/SubscriptionHistory');
    }

    public function getCopyTradeTransactions(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = CopyTradeTransaction::query()
            ->with(['tradingUser:meta_login,name,company', 'master', 'master.tradingUser'])
            ->where('user_id', Auth::id());

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('master.tradingUser', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('meta_login', 'like', $search)
                        ->orWhere('company', 'like', $search);
                })
                    ->orWhereHas('tradingUser', function ($to_wallet) use ($search) {
                        $to_wallet->where('name', 'like', $search)
                            ->orWhere('company', 'like', $search);
                    });
            })
                ->orWhere('meta_login', 'like', $search);
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $trade_rebates = $query->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json($trade_rebates);
    }

    public function getSubscriberAccounts(Request $request)
    {
        $user = Auth::user();

        $subscribers = Subscriber::with(['master', 'master.tradingUser', 'tradingUser:id,name,meta_login', 'subscription'])
            ->where('user_id', $user->id)
            ->whereNot('status', 'Rejected')
            ->orderByRaw("
        CASE
            WHEN status = 'Subscribing' THEN 1
            WHEN status = 'Expiring' THEN 2
            WHEN status = 'Switched' THEN 3
            WHEN status = 'Unsubscribed' THEN 4
            ELSE 5
        END")
            ->latest()
            ->get();

        $subscribers->each(function ($subscriber) use ($user) {
            $approvalDate = Carbon::parse($subscriber->approval_date);
            $today = Carbon::today();
            $join_days = $approvalDate->diffInDays($subscriber->status == 'Unsubscribed' ? $subscriber->unsubscribe_date : $today);
            $subscription_batches = $subscriber->subscription_batches;
            $expiredDate = Carbon::parse($subscriber->subscription->expired_date);
            $daysDifference = $approvalDate->diffInDays($expiredDate);

            $penalty_exempt = 0;

            foreach ($subscription_batches as $batch) {
                $penalty_days = $subscriber->master->masterManagementFee->last()->penalty_days;
                $penalty_exempt_date = $batch->created_at->addDays($penalty_days);

                if ($today > $penalty_exempt_date) {
                    $penalty_exempt += $batch->meta_balance;
                }
            }

            $management_fee = MasterManagementFee::where('master_id', $subscriber->master_id);

            $locale = app()->getLocale();
            $availableMaster = Master::with('tradingUser:id,name,company')
                ->where('status', 'Active')
                ->where('signal_status', 1)
                ->whereNot('id', $subscriber->master_id);

            $first_leader = $user->getFirstLeader();
            if ($user->is_public == 0 && $first_leader) {
                $leader = $first_leader;
                while ($leader && $leader->masterAccounts->isEmpty()) {
                    $leader = $leader->getFirstLeader();
                }

                if ($leader) {
                    $availableMaster = $availableMaster
                        ->where('is_public', $leader->is_public)
                        ->whereIn('user_id', $leader->masterAccounts->pluck('user_id'));
                } else {
                    // If leader is null, reset $availableMaster to an empty query
                    $availableMaster = $availableMaster->where('id', null);
                }
            } elseif ($user->is_public == 1 && $first_leader) {
                $availableMaster->where('is_public', $first_leader->is_public);
            } else {
                if ($user->is_public == 0) {
                    $availableMaster->where('is_public', $user->is_public)
                        ->whereIn('id', $user->masterAccounts->pluck('id'));
                } else {
                    $availableMaster->where('is_public', $user->is_public);
                }
            }

            $masterSel = $availableMaster->get()
                ->map(function ($master) use ($locale) {
                    return [
                        'value' => $master->id,
                        'label' => ($locale == 'cn' ? $master->tradingUser->company : $master->tradingUser->name) . ' ('. $master->meta_login . ')',
                    ];
                });

            $subscriber->join_days = $join_days;
            $subscriber->subscription_amount = $subscription_batches->sum('meta_balance');
            $subscriber->management_period = $subscriber->master->masterManagementFee->sum('penalty_days');
            $subscriber->management_fee = $management_fee->where('penalty_days', '>', $join_days)->first()->penalty_percentage;
            $subscriber->management_fee_for_stop_renewal = $management_fee->where('penalty_days', '>', $daysDifference)->first()->penalty_percentage;
            $subscriber->penalty_exempt = $penalty_exempt;
            $subscriber->newMasterSel = $masterSel;
        });

        return response()->json([
            'subscribers' => $subscribers
        ]);
    }

    public function getPenaltyDetail(Request $request)
    {
        $penalty = SubscriptionPenaltyLog::where('subscription_batch_id', $request->subscription_batch_id)->first();
        return response()->json($penalty);
    }

    public function terminateBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'terms' => ['accepted']
        ])->setAttributeNames([
            'terms' => trans('public.terms_and_conditions'),
        ]);

        $subscription_batch = SubscriptionBatch::find($request->id);

        if ($subscription_batch->status == 'Terminated') {
            return redirect()->back()
                ->with('title', trans('public.terminated'))
                ->with('warning', trans('public.terminated_subscription_error'));
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $subscription_batch->update([
                'auto_renewal' => false,
                'termination_date' => now(),
                'status' => 'Terminated'
            ]);

            $subscriber = Subscriber::find($subscription_batch->subscriber_id);
            $subscriber->subscribe_amount -= $subscription_batch->meta_balance;
            $subscriber->save();

            $subscription = Subscription::find($subscription_batch->subscription_id);
            $subscription->meta_balance -= $subscription_batch->meta_balance;
            $subscription->save();

            return redirect()->back()
                ->with('title', trans('public.success_terminate'))
                ->with('success', trans('public.successfully_terminate'). ': ' . $subscription->subscription_number);
        }
    }

    public function getSelectedNewMaster(Request $request)
    {
        $masterAccounts = Master::with([
            'user:id,username,name,email',
            'tradingAccount:id,meta_login,balance,equity',
            'tradingUser:id,name,company',
            'masterManagementFee'
        ])->find($request->master_id);

        return response()->json($masterAccounts);
    }

    public function switchMaster(Request $request)
    {
        $subscriber = Subscriber::find($request->subscriber_id);
        $new_master = Master::find($request->new_master_id);
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
        $userTrade = $metaService->userTrade($subscriber->meta_login);

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', trans('public.server_under_maintenance'))
                ->with('warning', trans('public.try_again_later'));
        }

        // check open trade
        if ($userTrade) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.user_got_trade'));
        }

        if (empty($new_master)) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.terminated_subscription_error'));
        }

        $switchMaster = SwitchMaster::where('user_id', $subscriber->user_id)
            ->where('new_master_id', $new_master->id)
            ->where('status', 'Pending')
            ->first();

        if ($switchMaster) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.terminated_subscription_error'));
        }

        SwitchMaster::create([
            'user_id' => $subscriber->user_id,
            'meta_login' => $subscriber->meta_login,
            'old_master_id' => $subscriber->master_id,
            'old_master_meta_login' => $subscriber->master_meta_login,
            'new_master_id' => $new_master->id,
            'new_master_meta_login' => $new_master->meta_login,
            'old_subscriber_id' => $subscriber->id,
            'status' => 'Pending',
        ]);

        return redirect()->back()
            ->with('title', trans('public.success_request_switch'))
            ->with('success', trans('public.successfully_request_switch', [
                'from' => $subscriber->master_meta_login,
                'to' => $new_master->meta_login
            ]));
    }
}
