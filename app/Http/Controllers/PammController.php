<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\MasterManagementFee;
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
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PammController extends Controller
{
    public function pamm_listing()
    {
        return Inertia::render('Pamm/PammListing/PammListing', [
            'terms' => Term::where('type', 'subscribe')->first(),
            'getTradingAccounts' => (new SelectOptionService())->getTradingAccounts()
        ]);
    }

    public function getPammMasters(Request $request)
    {
        $user = Auth::user();

        $subscriber = Subscriber::with(['master', 'master.tradingUser', 'tradingUser:id,name,meta_login', 'subscription'])
            ->where('meta_login', $request->meta_login)
            ->where('status', 'Subscribing')
            ->latest()
            ->first();

        $approvalDate = Carbon::parse($subscriber->approval_date > now() ? now() : $subscriber->approval_date);
        $today = Carbon::today();
        $join_days = $approvalDate->diffInDays($subscriber->status == 'Unsubscribed' ? $subscriber->unsubscribe_date : $today);
        $subscription_batches = $subscriber->subscription_batches;
        $expiredDate = $subscriber->subscription ? Carbon::parse($subscriber->subscription->expired_date) : null;
        $daysDifference = $approvalDate->diffInDays($expiredDate) ?? 0;

//            $penalty_exempt = 0;
//
//            foreach ($subscription_batches as $batch) {
//                $penalty_days = $subscriber->master->masterManagementFee->last()->penalty_days;
//                $penalty_exempt_date = $batch->created_at->addDays($penalty_days);
//
//                if ($today > $penalty_exempt_date) {
//                    $penalty_exempt += $batch->meta_balance;
//                }
//            }
//
//            $management_fee = MasterManagementFee::where('master_id', $subscriber->master_id);

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

//            $masterSel = $availableMaster->get()
//                ->map(function ($master) use ($locale) {
//                    return [
//                        'value' => $master->id,
//                        'label' => ($locale == 'cn' ? $master->tradingUser->company : $master->tradingUser->name) . ' ('. $master->meta_login . ')',
//                    ];
//                });

        $subscriber->join_days = $join_days;
        $subscriber->subscription_amount = $subscription_batches->sum('meta_balance');
        $subscriber->progressWidth = ($subscriber->subscribe_amount / $subscriber->max_out_amount) * 100;
//            $subscriber->management_period = $subscriber->master->masterManagementFee->sum('penalty_days');
//            $subscriber->management_fee = $management_fee->where('penalty_days', '>', $join_days)->first()->penalty_percentage;
//            $subscriber->management_fee_for_stop_renewal = $management_fee->where('penalty_days', '>', $daysDifference)->first()->penalty_percentage ?? 0;
//            $subscriber->penalty_exempt = $penalty_exempt;
//            $subscriber->newMasterSel = $masterSel;

        return response()->json([
            'subscriber' => $subscriber
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
