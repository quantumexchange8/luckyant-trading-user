<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\TradingAccount;
use App\Models\Transaction;
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
        return Inertia::render('Trading/MasterListing');
    }

    public function getMasterAccounts(Request $request)
    {
        $masterAccounts = Master::with(['user:id,name,email', 'tradingAccount:id,meta_login,balance,equity'])
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
            $master->subscribersCount = $master->subscribers->count();
            $master->totalFundWidth = (($totalSubscriptionsFee + $master->extra_fund) / $master->total_fund) * 100;
        });

        return response()->json($masterAccounts);
    }

    public function subscribeMaster(Request $request)
    {
        $user = Auth::user();
        $meta_login = $request->meta_login;
        $masterAccount = Master::find($request->master_id);
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return redirect()->back()
                ->with('title', 'Server under maintenance')
                ->with('warning', 'Please try again later');
        }

        try {
            $metaService->getUserInfo($user->tradingAccounts);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        $tradingAccount = TradingAccount::where('meta_login', $meta_login)->first();

        if ($tradingAccount->equity < $masterAccount->min_join_equity || $tradingAccount->equity < $masterAccount->subscription_fee) {
            throw ValidationException::withMessages(['meta_login' => trans('public.Insufficient balance')]);
        }

        if ($masterAccount->subscription_fee > 0) {
            // Create transaction
            $deal = [];
            try {
                $deal = (new MetaFiveService())->createDeal($meta_login, $masterAccount->subscription_fee, 'Subscription Fee for COPYTRADE', dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error fetching trading accounts: '. $e->getMessage());
            }

            $transaction_number = RunningNumberService::getID('transaction');

            $transaction = Transaction::create([
                'category' => 'trading_account',
                'user_id' => $user->id,
                'from_meta_login' => $meta_login,
                'ticket' => $deal['deal_Id'],
                'transaction_number' => $transaction_number,
                'transaction_type' => 'SubscriptionFee',
                'amount' => $masterAccount->subscription_fee,
                'transaction_charges' => 0,
                'transaction_amount' => $masterAccount->subscription_fee,
                'status' => 'Success',
                'comment' => $deal['conduct_Deal']['comment'],
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
            'next_pay_date' => today()->addDays($masterAccount->roi_period)
        ]);

        Subscriber::create([
            'user_id' => $user->id,
            'trading_account_id' => $tradingAccount->id,
            'meta_login' => $meta_login,
            'master_id' => $masterAccount->id,
            'master_meta_login' => $masterAccount->meta_login,
            'subscription_id' => $subscription->id,
        ]);

        $metaService->disableTrade($meta_login);

        return redirect()->back()
            ->with('title', 'Success subscribe')
            ->with('success', 'Successfully subscribe to LOGIN: ' . $masterAccount->meta_login);
    }

    public function getSubscriptions(Request $request)
    {
        $masterAccounts = Subscriber::with(['user:id,name,email', 'tradingAccount:id,meta_login,balance,equity', 'master', 'subscription'])
            ->where('user_id', Auth::id())
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
            $subscriber->master->user->profile_photo = $subscriber->master->user->getFirstMediaUrl('profile_photo');
        });

        return response()->json($masterAccounts);
    }

    public function masterListingDetail($masterListingDetail)
    {

        $masterListingDetail = Master::with(['user:id,name,email', 'tradingAccount:id,meta_login,balance,equity'])
            ->where('status', 'Active')
            ->where('signal_status', 1)
            ->whereNot('user_id', \Auth::id())
            ->where('id', $masterListingDetail)
            ->first();

        return Inertia::render('Trading/MasterListing/MasterListingDetail', [
            'masterListingDetail' => $masterListingDetail,
        ]);
    }

}
