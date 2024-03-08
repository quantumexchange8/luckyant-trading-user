<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use App\Models\TradeRebateSummary;
use Illuminate\Support\Facades\Log;
use App\Models\SettingPaymentMethod;
use App\Http\Requests\DepositRequest;
use App\Services\SelectOptionService;
use App\Models\CurrencyConversionRate;
use App\Services\RunningNumberService;
use App\Http\Requests\WithdrawalRequest;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function wallet()
    {

        $user = Auth::user();

        $wallets = Wallet::where('user_id', $user->id)->get();

        $totalDeposit = Transaction::where('user_id', $user->id)
            ->where('category', 'wallet')
            ->where('transaction_type', 'Deposit')
            ->where('status', 'Success')
            ->sum('transaction_amount');

        $totalWithdrawal = Transaction::where('user_id', $user->id)
            ->where('category', 'wallet')
            ->where('transaction_type', 'Withdrawal')
            ->where('status', 'Success')
            ->sum('transaction_amount');

        $totalRebate = TradeRebateSummary::where('upline_user_id', $user->id)
            ->where('status', 'Approved')
            ->sum('rebate');


        return Inertia::render('Transaction/Wallet/Wallet', [
            'wallets' => $wallets,
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
            'paymentAccountSel' => (new SelectOptionService())->getPaymentAccountSelection(),
            'paymentDetails' => SettingPaymentMethod::where('status', 'Active')->latest()->first(),
            'withdrawalFee' => Setting::where('slug', 'withdrawal-fee')->first(),
            'totalDeposit' => $totalDeposit,
            'totalWithdrawal' => $totalWithdrawal,
            'totalRebate' => $totalRebate,
        ]);
    }

    public function getWalletHistory(Request $request, $wallet_id)
    {

        $user = \Auth::user();

        $wallet = Transaction::query()
        ->where('user_id', $user->id)
        ->where('category', '=', 'wallet')
        ->with([
            'user:id,name,email',
            'from_wallet:id,name,type,balance,wallet_address',
            'to_wallet:id,name,type,balance,wallet_address',
            'from_meta_login:id,meta_login',
            'to_meta_login:id,meta_login',
        ])
        ->where('transactions.to_wallet_id', $wallet_id)
        ->orWhere('transactions.from_wallet_id', $wallet_id);

        // $wallet = $wallet->whereNotNull('from_wallet_id')->orWhereNotNull('to_wallet_id')->get();
        // dd($wallet);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $wallet->where(function ($q) use ($search) {
                $q->Where('transaction_number', 'like', $search)
                    ->orWhere('amount', 'like', $search);
            });
        }

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $wallet->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $wallet->where('transaction_type', $type);
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $wallet->whereBetween('created_at', [$start_date, $end_date]);
        }

        $results = $wallet->latest()->paginate(10);

        return response()->json($results);
    }

    public function tradingAccount()
    {

        return Inertia::render('Transaction/TradingAccount/TradingAccount');
    }

    public function getTradingHistory(Request $request)
    {

        $user = \Auth::user();

        $tradingAccount = Transaction::query()
        ->where('user_id', $user->id)
        ->where('category', '=', 'trading_account')
        // ->whereNotNull('from_wallet_id')
        // ->orWhereNotNull(['to_wallet_id'])
        ->with([
            'user:id,name,email',
            'from_wallet:id,name,type,balance,wallet_address',
            'to_wallet:id,name,type,balance,wallet_address',
            'from_meta_login:id,meta_login',
            'to_meta_login:id,meta_login',
        ]);

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $tradingAccount->where(function ($q) use ($search) {
                $q->Where('transaction_number', 'like', $search)
                    ->orWhere('amount', 'like', $search);
            });
        }

        if ($request->filled('filter')) {
            $filter = $request->input('filter') ;
            $tradingAccount->where(function ($q) use ($filter) {
                $q->where('status', $filter);
            });
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $tradingAccount->where('transaction_type', $type);
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $tradingAccount->whereBetween('created_at', [$start_date, $end_date]);
        }

        $results = $tradingAccount->latest()->paginate(10);

        return response()->json($results);
    }

    public function deposit(DepositRequest $request)
    {
        $user = Auth::user();
        $wallet = Wallet::find($request->wallet_id);
        $amount = number_format(floatval($request->amount), 2, '.', '');

        $transaction_number = RunningNumberService::getID('transaction');

        $transaction = Transaction::create([
            'category' => 'wallet',
            'user_id' => $user->id,
            'to_wallet_id' => $wallet->id,
            'setting_payment_method_id' => $request->setting_payment_id,
            'payment_method' => $request->payment_method,
            'transaction_number' => $transaction_number,
            'to_wallet_address' => $request->account_no,
            'transaction_type' => 'Deposit',
            'amount' => $amount,
            'transaction_charges' => 0,
            'conversion_rate' => $request->conversion_rate ?? 0,
            'transaction_amount' => $request->transaction_amount > 0 ? $request->transaction_amount : $amount,
            'status' => 'Processing',
        ]);

        if ($request->hasfile('receipt')){
            $transaction->addMedia($request->receipt)->toMediaCollection('receipt');
        }

        if ($request->payment_method == 'Payment Merchant') {
            $domain = $_SERVER['HTTP_HOST'];
            $paymentGateway = config('payment-gateway');
            $intAmount = intval($amount * 100);

            if ($domain === 'member.luckyantfxasia.com') {
                $selectedPaymentGateway = $paymentGateway['live'];
            } else {
                $selectedPaymentGateway = $paymentGateway['staging'];
            }

            // vCode
            $vCode = md5($intAmount . $selectedPaymentGateway['appId'] . $transaction_number . $selectedPaymentGateway['vKey']);

            $params = [
                'amount' => $intAmount,
                'orderNumber' => $transaction_number,
                'userId' => $user->id,
                'vCode' => $vCode,
            ];

            // Send response
            $baseUrl = $selectedPaymentGateway['paymentUrl'];
            $redirectUrl = $baseUrl . "?" . http_build_query($params);

            return Inertia::location($redirectUrl);
        }

        return redirect()->back()
            ->with('title', 'Success request deposit')
            ->with('success', 'Successfully submit a deposit request, we will email you once the deposit is processed');
    }

    public function depositReturn(Request $request)
    {
        $data = $request->all();

        Log::debug($data);

        $result = [
            'amount' => $data['amount'],
            'userId' => $data['userId'],
            'vCode' => $data['vCode'],
            'orderNumber' => $data['orderNumber'],
            'transactionId' => $data['transactionId'],
            'walletAddress' => $data['walletAddress'],
            'status' => $data['status'],
            'sCode' => $data['sCode'],
            'transactionHash' => $data['transactionHash'],
            'sourceAddress' => $data['sourceAddress'],
            'blockTime' => $data['blockTime'],
            'paidTime' => $data['paidTime'],
            'receivedAmount' => $data['receivedAmount'],
        ];

        $domain = $_SERVER['HTTP_HOST'];
        $paymentGateway = config('payment-gateway');

        if ($domain === 'member.luckyantfxasia.com') {
            $selectedPaymentGateway = $paymentGateway['live'];
        } else {
            $selectedPaymentGateway = $paymentGateway['staging'];
        }

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway['appId'] . $selectedPaymentGateway['sKey']);

        if ($result['sCode'] == $sCode2) {
            $transaction = Transaction::where('user_id', $result['userId'])->where('transaction_number', $result['orderNumber'])->first();
            $wallet = Wallet::find($transaction->to_wallet_id);

            if ($transaction->status == 'Processing') {
                if ($result['status'] == 'PAID') {
                    $intAmount = $transaction->transaction_amount * 100;
                    if ($result['receivedAmount'] != $intAmount) {
                        $charges = $intAmount - $result['receivedAmount'];
                        $transaction->update([
                            'transaction_charges' => $charges / 100,
                            'transaction_amount' => $result['receivedAmount'] / 100
                        ]);
                    } else {
                        $transaction->update([
                            'transaction_amount' => $result['receivedAmount'] / 100
                        ]);
                    }

                    $transaction->update([
                        'txn_hash' => $result['transactionHash'],
                        'status' => 'Success'
                    ]);

                    $walletTotalBalance = $wallet->balance + $transaction->transaction_amount;
                    $wallet->update([
                        'balance' => $walletTotalBalance,
                    ]);

                } elseif ($result['status'] == 'EXPIRED') {
                    $transaction->update([
                        'status' => 'Failed'
                    ]);
                }
            }
        }

        return to_route('dashboard');
    }

    public function depositCallback(Request $request)
    {
        $data = $request->all();

        Log::debug($data);

        $result = [
            'amount' => $data['amount'],
            'userId' => $data['userId'],
            'vCode' => $data['vCode'],
            'orderNumber' => $data['orderNumber'],
            'transactionId' => $data['transactionId'],
            'walletAddress' => $data['walletAddress'],
            'status' => $data['status'],
            'sCode' => $data['sCode'],
            'transactionHash' => $data['transactionHash'],
            'sourceAddress' => $data['sourceAddress'],
            'blockTime' => $data['blockTime'],
            'paidTime' => $data['paidTime'],
            'receivedAmount' => $data['receivedAmount'],
        ];

        $domain = $_SERVER['HTTP_HOST'];
        $paymentGateway = config('payment-gateway');

        if ($domain === 'member.luckyantfxasia.com') {
            $selectedPaymentGateway = $paymentGateway['live'];
        } else {
            $selectedPaymentGateway = $paymentGateway['staging'];
        }

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway['appId'] . $selectedPaymentGateway['sKey']);

        if ($result['sCode'] == $sCode2) {
            $transaction = Transaction::where('user_id', $result['userId'])->where('transaction_number', $result['orderNumber'])->first();
            $wallet = Wallet::find($transaction->to_wallet_id);

            if ($transaction->status == 'Processing') {
                if ($result['status'] == 'PAID') {
                    $intAmount = $transaction->transaction_amount * 100;
                    if ($result['receivedAmount'] != $intAmount) {
                        $charges = $intAmount - $result['receivedAmount'];
                        $transaction->update([
                            'transaction_charges' => $charges / 100,
                            'transaction_amount' => $result['receivedAmount'] / 100
                        ]);
                    } else {
                        $transaction->update([
                            'transaction_amount' => $result['receivedAmount'] / 100
                        ]);
                    }

                    $transaction->update([
                        'txn_hash' => $result['transactionHash'],
                        'status' => 'Success'
                    ]);

                    $walletTotalBalance = $wallet->balance + $transaction->transaction_amount;
                    $wallet->update([
                        'balance' => $walletTotalBalance,
                    ]);

                } else {
                    $transaction->update([
                        'txn_hash' => $result['transactionHash'],
                        'status' => 'Reject'
                    ]);
                }
            }
        }

    }

    public function withdrawal(WithdrawalRequest $request)
    {
        $user = \Auth::user();
        $amount = number_format(floatval($request->amount), 2, '.', '');
        $paymentAccount = PaymentAccount::find($request->wallet_address);
        $conversion_rate = CurrencyConversionRate::where('base_currency', $paymentAccount->currency)->first();

        $wallet = Wallet::find($request->wallet_id);
        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('Insufficient balance')]);
        }
        $withdrawal_fee = $request->transaction_charges;
        $final_amount = $amount - $withdrawal_fee;
        $wallet->balance -= $amount;
        $wallet->save();

        $transaction_amount = $final_amount * $conversion_rate->withdrawal_rate;
        $transaction_number = RunningNumberService::getID('transaction');

        Transaction::create([
            'category' => 'wallet',
            'user_id' => $user->id,
            'from_wallet_id' => $wallet->id,
            'transaction_number' => $transaction_number,
            'payment_account_id' => $paymentAccount->id,
            'payment_method' => $paymentAccount->payment_platform,
            'to_wallet_address' => $paymentAccount->account_no,
            'transaction_type' => 'Withdrawal',
            'amount' => $amount,
            'conversion_rate' => $conversion_rate->withdrawal_rate,
            'transaction_charges' => $withdrawal_fee,
            'transaction_amount' => $transaction_amount,
            'new_wallet_amount' => $wallet->balance,
            'status' => 'Processing',
        ]);

        return redirect()->back()->with('title', trans('public.Submitted'))->with('success', trans('public.Successfully Submitted Withdrawal Request'));
    }

    public function getPaymentDetails(Request $request)
    {

        $paymentDetails = SettingPaymentMethod::query()
            ->where('country', $request->countryId)
            ->where('status', 'Active')
            ->with(['country']);

        $results = $paymentDetails->latest()->get();

        return response()->json($results);
    }

    public function getBalanceChart()
    {
        $user = \Auth::user();

        $wallets = Wallet::query()
            ->where('user_id', $user->id)
            ->select('id', 'name', 'type', 'balance')
            ->get();

        $walletColors = [
            'Cash Wallet' => '#598fd8',
            'Rebate Wallet' => '#a855f7',
        ];

        $chartData = [
            'labels' => $wallets->pluck('name'),
            'datasets' => [],
        ];

        foreach ($wallets as $wallet) {
            $balances[] = $wallet->balance;

            $backgroundColors[] = $walletColors[$wallet->name] ?? '#000000';
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
}
