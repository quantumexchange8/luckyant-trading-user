<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Services\MetaFiveService;
use App\Models\SettingPaymentMethod;
use App\Services\SelectOptionService;
use App\Models\CurrencyConversionRate;
use App\Services\RunningNumberService;
use App\Http\Requests\InternalTransferRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\InternalTransferBalanceRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $announcement = Announcement::where('type', 'login')->latest()->first();

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

        return Inertia::render('Dashboard', [
            'announcement' => $announcement,
            'firstTimeLogin' => \Session::get('first_time_logged_in'),
            'walletSel' => (new SelectOptionService())->getInternalTransferWalletSelection(),
            'paymentAccountSel' => (new SelectOptionService())->getPaymentAccountSelection(),
            'paymentDetails' => $PaymentBankDetails,
            'PaymentCryptoDetails' => $PaymentCryptoDetails,
            'countries' => $formattedCurrencies,
            'withdrawalFee' => Setting::where('slug', 'withdrawal-fee')->first(),
            'registerLink' => $registerLink,
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

        $transaction = Transaction::where('user_id', $user->id)->where('category', 'wallet')->where('status', 'Success');

        return response()->json([
            'totalDeposit' => $transaction->where('transaction_type', 'Deposit')->sum('transaction_amount'),
            'totalWithdrawal' => $transaction->where('transaction_type', 'Withdrawal')->sum('transaction_amount'),
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

    public function internalTransferWallet(InternalTransferRequest $request)
    {
        $user = \Auth::user();
        $from_wallet = Wallet::where('id', $request->from_wallet)->first();
        $to_wallet = Wallet::where('id', $request->to_wallet)->first();
        $amount = $request->amount;

        if ($from_wallet->id == $to_wallet->id) {
            throw ValidationException::withMessages([
                'from_wallet' => trans('public.same_wallet_error'),
        ]);
        }

        // Check if balance is sufficient
        if ($from_wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        $transaction_number = RunningNumberService::getID('transaction');

        // Create transaction
        $transaction = Transaction::create([
            'category' => 'wallet',
            'user_id' => $user->id,
            'from_wallet_id' => $from_wallet->id,
            'to_wallet_id' => $to_wallet->id,
            'transaction_number' => $transaction_number,
            'transaction_type' => 'InternalTransfer',
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Success',
        ]);

        // Update the wallet balance
        $from_wallet->update([
            'balance' => $from_wallet->balance - $transaction->transaction_amount,
        ]);

        $to_wallet->update([
            'balance' => $to_wallet->balance + $transaction->transaction_amount,
        ]);

        $transaction->update([
            'new_wallet_amount' => $to_wallet->balance,
        ]);

        return redirect()->back()
            ->with('title', trans('public.success_internal_transaction'))
            ->with('success', trans('public.successfully_transfer') . ' $' . number_format($amount, 2) . ' ' . trans('public.from_wallet') . ': ' . $from_wallet->name . ' ' . trans('public.to_wallet') . ': ' . $to_wallet->name);
    }

}
