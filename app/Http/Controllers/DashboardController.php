<?php

namespace App\Http\Controllers;

use App\Models\PerformanceIncentive;
use App\Models\Subscription;
use App\Models\TradeHistory;
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
        $announcement = Announcement::where('type', 'login')->where('status', 'Active')->latest()->first();

        $PaymentBankDetails = SettingPaymentMethod::where('payment_method', 'Bank')->where('status', 'Active')->get();
        $PaymentCryptoDetails = SettingPaymentMethod::where('payment_method', 'Bank')->where('status', 'Active')->get();

        if (!empty($announcement)) {
            $announcement->image = $announcement->getFirstMediaUrl('announcement');
        }

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

        $rank = SettingRank::where('id', \Auth::user()->setting_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        return Inertia::render('Dashboard', [
            'announcement' => $announcement,
            'firstTimeLogin' => \Session::get('first_time_logged_in'),
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'eWalletSel' => (new SelectOptionService())->getInternalTransferWalletSelection(),
            'paymentAccountSel' => (new SelectOptionService())->getPaymentAccountSelection(),
            'paymentDetails' => $PaymentBankDetails,
            'PaymentCryptoDetails' => $PaymentCryptoDetails,
            'countries' => $formattedCurrencies,
            'withdrawalFee' => Setting::where('slug', 'withdrawal-fee')->first(),
            'withdrawalFeePercentage' => Setting::where('slug', 'withdrawal-fee-percentage')->first(),
            'registerLink' => $registerLink,
            'rank' => $translations[$locale] ?? $rank->name,
            'total_global_trading_lot_size' => Setting::where('slug', 'total-global-trading-lot-size')->first(),
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

        $totalWithdrawal = $transaction->where('transaction_type', 'Withdrawal')->sum('transaction_amount');

        $metaLogins = TradingAccount::where('user_id', $user->id)->get()->pluck('meta_login');

        $totalProfit = TradeHistory::whereIn('meta_login', $metaLogins)->where('trade_status', 'Closed')->sum('trade_profit');

        $tradeRebateSummary = TradeRebateSummary::where('upline_user_id', auth()->user()->id)
            ->where('status', 'Approved');

        $performanceIncentive = PerformanceIncentive::where('user_id', $user->id)->sum('personal_bonus_amt');

        return response()->json([
            'totalDeposit' => $totalDeposit,
            'totalWithdrawal' => $totalWithdrawal,
            'totalProfit' => $totalProfit,
            'totalRebateEarn' => $tradeRebateSummary->sum('rebate'),
            'performanceIncentive' => $performanceIncentive
        ]);
    }

    public function getPaymentDetails(Request $request)
    {
        $settingPaymentId = $request->id;
        $settingPaymentType = $request->type;

        if ($settingPaymentId) {
            $settingPayment = SettingPaymentMethod::with('country:id,name')->find($request->id);
        } else {
            $settingPayment = SettingPaymentMethod::with('country:id,name')->where('payment_method', $settingPaymentType)->inRandomOrder()->first();
        }

        $conversionRate = CurrencyConversionRate::where('base_currency', $settingPayment->currency)->first();

        return response()->json([
            'settingPayment' => $settingPayment,
            'conversionRate' => $conversionRate,
        ]);
    }

}
