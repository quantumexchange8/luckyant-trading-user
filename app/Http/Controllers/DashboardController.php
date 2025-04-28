<?php

namespace App\Http\Controllers;

use App\Models\CopyTradeHistory;
use App\Models\PammSubscription;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayToLeader;
use App\Models\PerformanceIncentive;
use App\Models\SettingPaymentToLeader;
use App\Models\Subscription;
use App\Models\TradeHistory;
use App\Models\WorldPoolAllocation;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\Setting;
use App\Models\SettingRank;
use App\Models\Transaction;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Services\MetaFiveService;
use App\Models\TradeRebateSummary;
use App\Models\SettingPaymentMethod;
use App\Services\SelectOptionService;
use App\Models\CurrencyConversionRate;

class DashboardController extends Controller
{
    public function index()
    {
        $query = Announcement::with([
            'leaders',
            'media'
        ])->where([
            'type' => 'login',
            'status' => 'Active'
        ]);

        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        if (empty($first_leader)) {
            $query->whereHas('leaders', function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id);
            });
        } else {
            $query->whereHas('leaders', function ($q) use ($first_leader) {
                $q->where('user_id', $first_leader->id);
            });
        }


        $announcements = $query->latest()
            ->get();

        $formattedCurrencies = SettingPaymentMethod::whereNotNull('country')->get()->map(function ($country) {
            return [
                'value' => $country->id,
                'label' => $country->payment_platform_name,
                'imgUrl' => $country->getFirstMediaUrl('payment_logo'),
            ];
        });

        $registerUrl = route('register');
        $registerLink = $registerUrl . '/' . \Auth::user()->referral_code;

        $locale = app()->getLocale();

        $rank = SettingRank::where('id', \Auth::user()->display_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        $world_pool_ranks = SettingRank::where('position', '>', 4)
            ->limit(2)
            ->get()
            ->pluck('name')
            ->toArray();

        $allocation_amount = WorldPoolAllocation::whereDate('allocation_date', '<=', Carbon::now())
            ->sum('allocation_amount');

        $world_pool = [];

        foreach ($world_pool_ranks as $index => $pool_rank) {
            if ($index === 0) {
                $world_pool[$pool_rank] = $allocation_amount;
            } else {
                $world_pool[$pool_rank] = $allocation_amount * 2;
            }
        }

        return Inertia::render('Dashboard', [
            'announcements' => $announcements,
            'eWalletSel' => (new SelectOptionService())->getInternalTransferWalletSelection(),
            'countries' => $formattedCurrencies,
            'withdrawalFee' => Setting::where('slug', 'withdrawal-fee')->first(),
            'withdrawalFeePercentage' => Setting::where('slug', 'withdrawal-fee-percentage')->first(),
            'registerLink' => $registerLink,
            'rank' => $translations[$locale] ?? $rank->name,
            'total_global_trading_lot_size' => Setting::where('slug', 'total-global-trading-lot-size')->first(),
            'world_pool' => $world_pool
        ]);
    }

    public function getBalanceChart()
    {
        $user = \Auth::user();

        // Get Cash Wallet balance
        $wallets = Wallet::query()
            ->where('user_id', $user->id)
            ->select('id', 'name', 'type', 'balance')
            ->get();

        // Get trading account balances
        $tradingAccounts = TradingAccount::where('user_id', $user->id)->get();

        $chartData = [
            'labels' => $wallets->pluck('name'), // Initial label for Cash Wallet
            'datasets' => [],
        ];

        foreach ($wallets as $wallet) {
            $balances[] = $wallet->balance;
        }
        $backgroundColors = ['#598fd8']; // Background color for Cash Wallet

        // Hardcoded 10 background colors for trading accounts
        $tradingAccountColors = ["#607d8b", "#42515c", "#3c474e", "#4575cb", "#0060ff", "#3b62ba", "#79ade1", "#2f4579", "#00ffff"];

        $i = 0; // Index to iterate through the tradingAccountColors array

        foreach ($tradingAccounts as $account) {
            $chartData['labels'][] = 'Login: ' . $account->meta_login;
            $balances[] = number_format($account->balance, 2, '.', '');

            // Assigning a different background color for each trading account
            $backgroundColors[] = $tradingAccountColors[$i];

            // Increment the index or loop back to the beginning if we reach the end of the tradingAccountColors array
            $i = ($i + 1) % count($tradingAccountColors);
        }

        $dataset = [
            'data' => $balances,
            'backgroundColor' => $backgroundColors,
            'offset' => 5,
            'borderColor' => 'transparent'
        ];

        $chartData['datasets'][] = $dataset;

        return response()->json($chartData);
    }

    public function update_session()
    {
        \Session::put('first_time_logged_in', 0);

        return back();
    }

    public function getWallets()
    {
        $user = \Auth::user();

        return response()->json($user->wallets);
    }

    public function getTotalTransactions()
    {
        $user = \Auth::user();

        $transaction = Transaction::where('user_id', $user->id)
            ->where('category', 'wallet')
            ->where('status', 'Success');

//        $withdrawalDemoFund = Transaction::where('user_id', $user->id)
//            ->where('fund_type', 'DemoFund')
//            ->where('transaction_type', 'Withdrawal')
//            ->where('status', 'Success')
//            ->sum('transaction_amount');

        $totalDeposit = Subscription::where('user_id',$user->id)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $totalPamm = PammSubscription::with('master')
            ->whereHas('master', function ($q) {
                $q->where('involves_world_pool', 1);
            })
            ->where('user_id',$user->id)
            ->where('status', 'Active')
            ->sum('subscription_amount');

        $totalWithdrawal = $transaction->where('transaction_type', 'Withdrawal')->sum('amount');

        $totalProfit = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'ProfitSharing')
            ->sum('transaction_amount');

        $meta_logins = TradingAccount::where('user_id', $user->id)
            ->get()
            ->pluck('meta_login');

        $totalProfitLoss = TradeHistory::whereIn('meta_login', $meta_logins)
            ->where('trade_status', 'Closed')
            ->sum('trade_profit');

        $tradeRebateSummary = TradeRebateSummary::where('upline_user_id', auth()->user()->id)
            ->where('status', 'Approved');

        $performanceIncentive = PerformanceIncentive::where('user_id', $user->id)->sum('personal_bonus_amt');

        return response()->json([
            'totalDeposit' => $totalDeposit + $totalPamm,
            'totalWithdrawal' => $totalWithdrawal,
            'totalProfit' => $totalProfit,
            'totalRebateEarn' => $tradeRebateSummary->sum('rebate'),
            'performanceIncentive' => $performanceIncentive,
            'totalProfitLoss' => $totalProfitLoss,
        ]);
    }

    public function getPaymentDetails(Request $request)
    {
        $settingPaymentTypes = $request->type;
        $user = Auth::user();
        $leader = $user->getFirstLeader();

        $setting_payment_ids = SettingPaymentToLeader::where('user_id', $leader->id)
            ->distinct('setting_payment_method_id')
            ->pluck('setting_payment_method_id')
            ->toArray();

        $paymentDetails = [];
        switch ($settingPaymentTypes) {
            case 'bank':
                $paymentDetails = SettingPaymentMethod::with([
                    'media',
                    'country:id,name'
                ])
                    ->whereIn('id', $setting_payment_ids)
                    ->where('payment_method', 'Bank')
                    ->get()
                    ->map(function ($payment) {
                        $payment->currency_rate = CurrencyConversionRate::firstWhere('base_currency', $payment->currency)->deposit_rate;

                        return $payment;
                    });
                break;

            case 'payment_service':
                $paymentDetails = SettingPaymentMethod::where('payment_method', 'Crypto')
                    ->whereIn('id', $setting_payment_ids)
                    ->where('status', 'Active')
                    ->first();

                $paymentDetails->currency_rate = 1;
                break;

            case 'payment_merchant':
                $leader = $user->getFirstLeader();
                if ($leader) {
                    $payment_gateway_ids = PaymentGatewayToLeader::where('user_id', $leader->id)
                        ->pluck('payment_gateway_id')
                        ->toArray();

                    $paymentDetails = PaymentGateway::select([
                        'id',
                        'name',
                        'platform',
                        'payment_app_name'
                    ])
                        ->whereIn('id', $payment_gateway_ids)
                        ->get();
                }
                break;
        }

        return response()->json([
            'paymentDetails' => $paymentDetails,
        ]);
    }

    public function getDepositOptions()
    {
        $options = ['payment_merchant'];

        $leader = Auth::user()->getFirstLeader();

        $setting_payment_ids = SettingPaymentToLeader::where('user_id', $leader->id)
            ->distinct('setting_payment_method_id')
            ->pluck('setting_payment_method_id')
            ->toArray();

        $settingPayments = SettingPaymentMethod::whereIn('id', $setting_payment_ids)
            ->where('status', 'Active')
            ->get();

        $paymentPlatforms = $settingPayments->pluck('payment_method')->unique();

        if ($paymentPlatforms->contains('Bank')) {
            $options[] = 'bank';
        }
        if ($paymentPlatforms->contains('Crypto')) {
            $options[] = 'payment_service';
        }

        $user = User::find(1137);
        $cryptocurrency_service_provider = false;

        if ($user) {
            $childrenIds = $user->getChildrenIds();
            $authUserId = Auth::id();

            if ($authUserId == $user->id || in_array($authUserId, $childrenIds)) {
                $cryptocurrency_service_provider = true;
            }
        }

        if ($cryptocurrency_service_provider) {
            $options = ['cryptocurrency_service_provider'];
        }

        return response()->json([
            'depositOptions' => $options
        ]);
    }
}
