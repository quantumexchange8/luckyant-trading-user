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
use App\Models\User;
use App\Models\Wallet;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use App\Services\SidebarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PammController extends Controller
{
    public function pamm_master_listing()
    {
        $user = User::find(1137);

        if ($user) {
            $childrenIds = $user->getChildrenIds();

            $authUserId = \Auth::id();

            if ($authUserId == $user->id || in_array($authUserId, $childrenIds)) {
                return redirect()->back();
            }
        }

        return Inertia::render('Pamm/PammMaster/PammMasterListing', [
            'title' => trans('public.pamm_master_listing'),
            'pammType' => 'StandardGroup'
        ]);
    }

    public function esg_investment_portfolio()
    {
        return Inertia::render('Pamm/PammMaster/PammMasterListing', [
            'terms' => Term::where('type', 'pamm_esg')->first(),
            'title' => trans('public.esg_investment_portfolio'),
            'pammType' => 'ESG'
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
            ->whereNot('user_id', $user->id);

        // Exclude accounts not visible to the first leader
        if ($first_leader) {
            $masterAccounts->whereJsonDoesntContain('not_visible_to', $first_leader->id);
        }

        // Handle public/private logic
        if ($user->is_public == 1) {
            // User is public
            if ($first_leader) {
                $masterAccounts->where('is_public', $first_leader->is_public);
            } else {
                $masterAccounts->where('is_public', 1);
            }
        } else {
            if ($first_leader) {
                $leader = $first_leader;
                while ($leader && $leader->masterAccounts->isEmpty()) {
                    $leader = $leader->getFirstLeader();
                }

                if ($leader) {
                    // Allow viewing leader's master accounts and public master accounts
                    $masterAccounts->where(function ($query) use ($leader) {
                        $query->where('is_public', 1) // Public master accounts
                        ->orWhere(function ($query) use ($leader) {
                            $query->where('is_public', $leader->is_public)
                                ->whereIn('user_id', $leader->masterAccounts->pluck('user_id'));
                        });
                    });
                } else {
                    // No valid leader found, only show public accounts
                    $masterAccounts->where('is_public', 1);
                }
            } else {
                // No leader, show public accounts and user's own master accounts
                $masterAccounts->where(function ($query) use ($user) {
                    $query->where('is_public', 1) // Public master accounts
                    ->orWhereIn('id', $user->masterAccounts->pluck('id')); // User's own master accounts
                });
            }
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $masterAccounts->where(function ($query) use ($search) {
                $query->whereHas('tradingAccount', fn($q) => $q->where('meta_login', 'like', $search))
                    ->orWhereHas('user', fn($q) => $q->where('name', 'like', $search)
                        ->orWhere('username', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        // Apply type filter
        if ($request->filled('type')) {
            $masterAccounts->where('type', $request->input('type'));
        }

        // Apply sort options
        if ($request->filled('sort')) {
            $sort = $request->input('sort');
            switch ($sort) {
                case 'max_equity':
                    $masterAccounts->orderByDesc('min_join_equity');
                    break;
                case 'min_equity':
                    $masterAccounts->orderBy('min_join_equity');
                    break;
                case 'max_sub':
                    $masterAccounts->withCount('subscribers')->orderByDesc('subscribers_count');
                    break;
                case 'min_sub':
                    $masterAccounts->withCount('subscribers')->orderBy('subscribers_count');
                    break;
                // Add more cases as needed for other sort values
            }
        }

        $masterAccounts = $masterAccounts->latest()->paginate(10);

        // Enhance master accounts with additional data
        $masterAccounts->each(function ($master) {
            $totalSubscriptionsFee = PammSubscription::where('master_id', $master->id)
                ->where('status', 'Active')
                ->sum('subscription_fee');

            // requires active count relation
            $pamm_subscription_count = PammSubscription::where('master_id', $master->id)->where('status', 'Active')->count();

            $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
            $master->total_subscription_amount = ($totalSubscriptionsFee + $master->total_fund) ?? 0;
            $master->total_subscribers = $master->total_subscribers + $pamm_subscription_count;
            $master->tnc_url = App::getLocale() == 'cn' ? $master->getFirstMediaUrl('cn_tnc_pdf') : $master->getFirstMediaUrl('en_tnc_pdf');
            $master->tree_tnc_url = App::getLocale() == 'cn' ? $master->getFirstMediaUrl('cn_tree_pdf') : $master->getFirstMediaUrl('en_tree_pdf');
            $master->totalFundWidth = ($master->total_fund == 0)
                ? ($totalSubscriptionsFee + $master->extra_fund)
                : (($totalSubscriptionsFee + $master->extra_fund) ?? 0) / $master->total_fund * 100;
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

        return redirect()->back()
            ->with('title', trans('public.success_subscribe'))
            ->with('success', trans('public.successfully_subscribe'). ': ' . $masterAccount->meta_login);
    }

    public function pamm_subscriptions()
    {
        return Inertia::render('Pamm/PammListing/PammListing', [
            'terms' => Term::where('type', 'pamm_esg')->first(),
            'getTradingAccounts' => (new SelectOptionService())->getTradingAccounts(),
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
        ]);
    }

    public function getPammSubscriptionData(Request $request)
    {
        $user = Auth::user();

        $subQuery = PammSubscription::select(DB::RAW('COALESCE(MIN(CASE WHEN status = "Active" THEN id END),MAX(id)) AS first_id'),
        DB::RAW('SUM(CASE WHEN status = "Active" THEN subscription_amount ELSE 0 END) AS total_amount'),
        DB::RAW('SUM(CASE WHEN status = "Active" THEN CAST(SUBSTRING_INDEX(subscription_package_product, "棵沉香树", 1) AS UNSIGNED) ELSE 0 END) AS package_amount'))
            ->where('user_id', $user->id)
            ->whereNot('status', 'Pending')
            ->groupBy('meta_login', 'master_id');

        $pamm_subscriptions = PammSubscription::with([
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
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->whereHas('master.tradingUser', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('meta_login', 'like', $search)
                        ->orWhere('company', 'like', $search);
                })
                    ->orWhere('pamm_subscriptions.meta_login', 'like', $search);
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('pamm_subscriptions.type', $request->type);
            })
            ->whereNot('pamm_subscriptions.status', 'Pending')
            ->get();

        $totalPenalties = []; // Array to store total penalties per master

        $pamm_subscriptions->each(function ($pamm) use (&$totalPenalties) {
            $approvalDate = Carbon::parse($pamm->approval_date > now() ? now() : $pamm->approval_date);
            $today = Carbon::today();
            $join_days = $approvalDate->diffInDays($pamm->status == 'Terminated' ? $pamm->termination_date : $today);

            $domain = $_SERVER['HTTP_HOST'];
            $canTopUp = false;

            if ($domain != 'member.luckyantmallvn.com' && $pamm->master->can_top_up) {
                $canTopUp = true;
            }

            $management_fee = MasterManagementFee::where('master_id', $pamm->master_id)
                ->where('penalty_days', '>', $join_days)
                ->first();

            $penalty = $pamm->subscription_amount * ($management_fee->penalty_percentage ?? 0) / 100;

            $pamm->join_days = $pamm->status != 'Rejected' ? $join_days : 0;
            $pamm->canTopUp = $canTopUp;
            $pamm->master->profile_pic = $pamm->master->user->getFirstMediaUrl('profile_photo');

            // Accumulate total penalties per master
            if (!isset($totalPenalties[$pamm->master_id])) {
                $totalPenalties[$pamm->master_id] = 0;
            }
            $totalPenalties[$pamm->master_id] += $penalty;
        });

        // Attach total penalties to each master in the response
        $pamm_subscriptions->each(function ($pamm) use ($totalPenalties) {
            $pamm->master->totalManagementPenalty = $totalPenalties[$pamm->master_id] ?? 0;
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

        $query = PammSubscription::with([
            'master',
            'master.tradingUser',
            'master.masterManagementFee',
            'tradingUser:id,name,meta_login',
            'package'
        ])
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

        $results->each(function ($pamm) {
            $approvalDate = Carbon::parse($pamm->approval_date > now() ? now() : $pamm->approval_date);

            $today = Carbon::today();

            $join_days = $approvalDate->diffInDays($pamm->status == 'Terminated' ? $pamm->termination_date : $today);

            $management_fee = MasterManagementFee::where('master_id', $pamm->master_id)
                ->where('penalty_days', '>', $join_days)
                ->first();

            $pamm->join_days = $pamm->status != 'Rejected' ? $join_days : 0;
            $pamm->management_fee = $management_fee->penalty_percentage ?? 0;
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

//        $response = Http::post('https://api.luckyantmallvn.com/serverapi/pamm/subscription/join', $pamm_subscription);
//        \Log::debug($response);

        $masterAccount->total_fund += $pamm_subscription->subscription_amount;
        $masterAccount->save();
//        $master_response = \Http::post('https://api.luckyantmallvn.com/serverapi/pamm/strategy', $masterAccount);
//        \Log::debug($master_response);

        return redirect()->back()
            ->with('title', trans('public.success_top_up'))
            ->with('success', trans('public.successfully_top_up'). ': ' . $masterAccount->meta_login);
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
            ->where('master_meta_login', $request->meta_master_login)
            ->whereIn('status', ['Active', 'Terminated'])
            ->get();

        foreach ($pamm_batches as $pamm_batch) {
            if ($pamm_batch->status == 'Revoked') {
                return redirect()->back()
                    ->with('title', trans('public.terminated_subscription'))
                    ->with('warning', trans('public.terminated_subscription_error'));
            }

            $pamm_batch->update([
                'termination_date' => now(),
                'status' => 'Revoked'
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
