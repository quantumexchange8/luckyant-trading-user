<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\TradingAccount;
use App\Services\MetaFiveService;
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
            $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
            $master->subscribersCount = $master->subscribers->count();
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

            // Create diff subscriptions data
            $subscriptionData = [
                'user_id' => $user->id,
                'trading_account_id' => $user->id,
                'meta_login' => $user->id,
                'transaction_id' => $user->id,
                'next_pay_date' => $user->id, // calculate
            ];
        } else {
            $subscriptionData = [
                'user_id' => $user->id,
                'trading_account_id' => $user->id,
                'meta_login' => $user->id,
            ];
        }

        $subscription = Subscription::create($subscriptionData);

        Subscriber::create([
            'user_id' => $user->id,
            'trading_account_id' => $tradingAccount->id,
            'meta_login' => $meta_login,
            'master_id' => $masterAccount->id,
            'subscription_id' => $subscription->id,
        ]);

        $metaService->disableTrade($meta_login);

        //block user deposit and withdraw to account
        return redirect()->back()
            ->with('title', 'Success subscribe')
            ->with('success', 'Successfully subscribe to LOGIN: ' . $masterAccount->meta_login);
    }
}
