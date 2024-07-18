<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowPammRequest;
use App\Http\Requests\JoinPammRequest;
use App\Models\Master;
use App\Models\MasterManagementFee;
use App\Models\MasterSubscriptionPackage;
use App\Models\PammSubscription;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionBatch;
use App\Models\Term;
use App\Models\TradingAccount;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use App\Services\SidebarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PammController extends Controller
{
    public function pamm_master_listing()
    {
        $getMasterVisibility = (new SidebarService())->getMasterVisibility();

        if (!$getMasterVisibility) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Pamm/PammMaster/PammMasterListing', [
            'terms' => Term::where('type', 'subscribe')->first(),
        ]);
    }

    public function getPammMasters(Request $request)
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
            ->where('category', 'pamm')
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
                $query->where('type', $request->type);
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sort = $request->input('type');
                switch ($sort) {
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
            $totalSubscriptionsFee = PammSubscription::where('master_id', $master->id)
                ->where('status', 'Active')
                ->sum('subscription_fee');

            $activePammSubscriptions = PammSubscription::where('user_id', Auth::id())
                ->where('master_id', $master->id)
                ->whereIn('status', ['Pending', 'Active'])
                ->get();

            if ($activePammSubscriptions->isEmpty()) {
                $master->allow_subscribe = true;
            } else {
                $master->allow_subscribe = false;
            }

            $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
            $master->total_subscription_amount = $totalSubscriptionsFee + $master->total_fund ?? 0;
            $master->totalFundWidth = $master->total_fund == 0 ? $totalSubscriptionsFee + $master->extra_fund : (($totalSubscriptionsFee + $master->extra_fund) ?? 0 / $master->total_fund) * 100 ;
        });

        return response()->json($masterAccounts);
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
        $amount = $request->amount;
        $wallet = Wallet::where('user_id', $user->id)->where('type', 'cash_wallet')->first();
        $masterAccount = Master::with('tradingAccount:id,meta_login,margin_leverage')->find($request->master_id);

        $max_out_amount = null;

        if ($request->amount_package_id) {
            $package = MasterSubscriptionPackage::find($request->amount_package_id);
            $max_out_amount = $package->max_out_amount;
        }

        if ($masterAccount->type == 'ESG' && empty($meta_login)) {
            //check wallet balance
            if ($wallet->balance < $amount) {
                throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
            }

            //join pamm
            $transaction = Transaction::create([
                'category' => 'wallet',
                'user_id' => $user->id,
                'from_wallet_id' => $wallet->id,
                'transaction_number' => RunningNumberService::getID('transaction'),
                'transaction_type' => 'PammEsg',
                'amount' => $amount,
                'transaction_charges' => 0,
                'transaction_amount' => $amount,
                'status' => 'Success',
                'new_wallet_amount' => $wallet->balance - $amount,
            ]);

            $wallet->balance -= $amount;
            $wallet->save();

            PammSubscription::create([
                'user_id' => $user->id,
                'master_id' => $masterAccount->id,
                'master_meta_login' => $masterAccount->meta_login,
                'subscription_amount' => $amount/2,
                'subscription_package_id' => $request->amount_package_id,
                'subscription_package_product' => $request->package_product,
                'type' => $masterAccount->type,
                'transaction_id' => $transaction->id,
                'subscription_number' => RunningNumberService::getID('subscription'),
                'subscription_period' => $masterAccount->join_period,
                'settlement_period' => $masterAccount->roi_period,
                'settlement_date' => now()->addDays($masterAccount->roi_period)->startOfDay(),
                'expired_date' => now()->addDays($masterAccount->join_period)->endOfDay(),
                'max_out_amount' => $max_out_amount,
                'delivery_address' => $request->delivery_address,
                'status' => 'Pending'
            ]);
        } else {
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
                return redirect()->back()
                    ->with('title', trans('public.invalid_action'))
                    ->with('warning', trans('public.user_got_trade'));
            }

            // check meta subscriber status
            if ($pamm_subscription) {
                return redirect()->back()
                    ->with('title', trans('public.invalid_action'))
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

            if ($tradingAccount->balance < $masterAccount->min_join_equity || $tradingAccount->balance < ($amount + $masterAccount->subscription_fee) || $tradingAccount->balance < $amount) {
                throw ValidationException::withMessages(['meta_login' => trans('public.insufficient_balance')]);
            }

            // Calculate the balance from package amount and trading acc balance
            $amount_balance = $tradingAccount->balance - $amount;

            $e_wallet = Wallet::where('user_id', $user->id)->where('type', 'e_wallet')->first();
            $amount_remain = $amount_balance;

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
                    $deal = $metaService->createDeal($meta_login, $amount_balance, 'Withdraw balance from trading account for pamm subscription', dealAction::WITHDRAW);
                } catch (\Exception $e) {
                    \Log::error('Error creating deal: ' . $e->getMessage());
                }

                if ($remainingBalance > 0) {
                    if ($remainingBalance >= $amount_balance) {
                        // Deduct full amount from e_wallet
                        $e_wallet->balance += $amount_balance;
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

            PammSubscription::create([
                'user_id' => $user->id,
                'meta_login' => $meta_login,
                'master_id' => $masterAccount->id,
                'master_meta_login' => $masterAccount->meta_login,
                'subscription_amount' => $amount/2,
                'type' => $masterAccount->type,
                'subscription_number' => RunningNumberService::getID('subscription'),
                'subscription_period' => $masterAccount->join_period,
                'settlement_period' => $masterAccount->roi_period,
                'settlement_date' => now()->addDays($masterAccount->roi_period)->startOfDay(),
                'expired_date' => $masterAccount->join_period > 0 ? now()->addDays($masterAccount->join_period)->endOfDay() : null,
                'max_out_amount' => $max_out_amount,
                'status' => 'Pending'
            ]);
        }

        return redirect()->back()
            ->with('title', trans('public.success_subscribe'))
            ->with('success', trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login);

//        dd($request->all());
//
//        $metaService = new MetaFiveService();
//        $connection = $metaService->getConnectionStatus();
//        $userTrade = $metaService->userTrade($meta_login);
//        $subscriber = Subscriber::where('meta_login', $meta_login)
//            ->whereIn('status', ['Pending', 'Subscribing'])
//            ->first();
//
//        // check open trade
//        if ($userTrade) {
//            return redirect()->back()
//                ->with('title', trans('public.invalid_action'))
//                ->with('warning', trans('public.user_got_trade'));
//        }
//
//        // check meta subscriber status
//        if ($subscriber) {
//            return redirect()->back()
//                ->with('title', trans('public.invalid_action'))
//                ->with('warning', trans('public.try_again_later'));
//        }
//
//        if ($connection != 0) {
//            return redirect()->back()
//                ->with('title', trans('public.server_under_maintenance'))
//                ->with('warning', trans('public.try_again_later'));
//        }
//
//        try {
//            $metaService->getUserInfo($user->tradingAccounts);
//        } catch (\Exception $e) {
//            \Log::error('Error fetching trading accounts: '. $e->getMessage());
//            return redirect()->back()
//                ->with('title', trans('public.server_under_maintenance'))
//                ->with('warning', trans('public.try_again_later'));
//        }
    }

    public function pamm_subscriptions()
    {
        return Inertia::render('Pamm/PammListing/PammListing', [
            'terms' => Term::where('type', 'subscribe')->first(),
            'getTradingAccounts' => (new SelectOptionService())->getTradingAccounts()
        ]);
    }

    public function getPammSubscriptionData(Request $request)
    {
        $user = Auth::user();

        $pamm_subscriptions = PammSubscription::with(['master', 'master.user','master.tradingUser', 'tradingUser:id,name,meta_login', 'package'])
            ->where('user_id', $user->id)
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->type);
            })
            ->whereNot('status', 'Pending')
            ->latest()
            ->paginate(10);

        $pamm_subscriptions->each(function ($pamm) use ($user) {
            $approvalDate = Carbon::parse($pamm->approval_date > now() ? now() : $pamm->approval_date);
            $today = Carbon::today();
            $join_days = $approvalDate->diffInDays($pamm->status == 'Terminated' ? $pamm->termination_date : $today);

            $pamm->join_days = $join_days;
            $pamm->master->profile_pic = $pamm->master->user->getFirstMediaUrl('profile_photo');
        });

        return response()->json([
            'pamm_subscriptions' => $pamm_subscriptions
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
            });
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
            $approvalDate = Carbon::parse($batch->approval_date > now() ? now() : $batch->approval_date);
            $today = Carbon::today();
            $join_days = $approvalDate->diffInDays($batch->status == 'Terminated' ? $batch->termination_date : $today);

            $batch->join_days = $join_days;
        });

        return response()->json($results);
    }
}
