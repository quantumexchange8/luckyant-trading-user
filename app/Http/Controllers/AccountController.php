<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Notifications\AddTradingAccountNotification;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        $live_account_quota = Setting::where('slug', 'live_account_quota')->first();

        return Inertia::render('Account/Account', [
            'activeAccountCounts' => TradingUser::where('user_id', Auth::id())->where('acc_status', 'Active')->count(),
            'liveAccountQuota' => intval($live_account_quota->value),
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'leverageSel' => (new SelectOptionService())->getActiveLeverageSelection(),
            'masterAccountLogin' => Master::where('user_id', Auth::id())->pluck('meta_login')->toArray(),
        ]);
    }

    public function createAccount(Request $request)
    {
        $user = Auth::user();

        $liveAccountQuota = Setting::where('slug', 'live_account_quota')->first()->value;

        if ($user->tradingUsers->where('acc_status', 'Active')->count() >= $liveAccountQuota) {
            return redirect()->back()
                ->with('title', trans('public.live_account_quota'))
                ->with('warning', trans('public.live_account_quota_warning'));
        }

        $type = $request->type;
        $group = AccountType::with('metaGroup')->where('id', $type)->get()->value('metaGroup.meta_group_name');
        $leverage = $request->leverage;

        if ($type == 'trading_account') {
            $metaService = new MetaFiveService();
            $connection = $metaService->getConnectionStatus();

            if ($connection != 0) {
                return redirect()->back()
                    ->with('title', trans('public.server_under_maintenance'))
                    ->with('warning', trans('public.try_again_later'));
            }

            $metaAccount = $metaService->createUser($user, $group, $leverage, $user->email);
            $balance = TradingAccount::where('meta_login', $metaAccount['login'])->value('balance');

            Notification::route('mail', $user->email)
                ->notify(new AddTradingAccountNotification($metaAccount, $balance, $user));

            return back()->with('toast', trans('public.created_trading_account'));
        } else {
            $virtual_account = RunningNumberService::getID('virtual_account');

            TradingAccount::create([
                'user_id' => $user->id,
                'meta_login' => $virtual_account,
                'account_type' => $type,
                'margin_leverage' => $leverage,
            ]);

            TradingUser::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'meta_login' => $virtual_account,
                'meta_group' => $group,
                'account_type' => $type,
                'leverage' => $leverage,
            ]);

            return back()->with('toast', trans('public.created_virtual_account'));
        }
    }

    public function getTradingAccountsData()
    {
        $user = Auth::user();
        $userTradingAccounts = TradingUser::select('id', 'user_id', 'meta_login', 'acc_status', 'account_type')
            ->where('user_id', $user->id)
            ->where('acc_status', 'Active')
            ->where('account_type', 1)
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
            'masterAccount:id,trading_account_id'
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
                    ->latest()->first();

                $data = $tradingAccount;

                if ($pamm_subscription_exist) {
                    $data['balance_in'] = false;
                    $data['balance_out'] = false;
                } elseif ($latest_unsubscribed && Carbon::parse($latest_unsubscribed->unsubscribe_date)->greaterThan(Carbon::now()->subHours(24))) {
                    $data['balance_in'] = true;
                    $data['balance_out'] = false;
                } elseif ($active_subscriber->whereIn('status', ['Subscribing', 'Expiring', 'Pending'])->exists()) {
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
            $start_date = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
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
}
