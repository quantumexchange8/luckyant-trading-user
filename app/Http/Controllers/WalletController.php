<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayToLeader;
use App\Models\TradingAccount;
use App\Notifications\DepositRequestNotification;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
use App\Http\Requests\TransferRequest;
use App\Models\CurrencyConversionRate;
use App\Services\RunningNumberService;
use App\Http\Requests\WithdrawalRequest;
use App\Notifications\TransferNotification;
use Illuminate\Support\Facades\Notification;
use App\Http\Requests\InternalTransferRequest;
use Illuminate\Validation\ValidationException;
use App\Notifications\DepositConfirmationNotification;

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
        ->where('to_wallet_id', $wallet_id)
        ->orWhere('from_wallet_id', $wallet_id);

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
        $payment_detail = $request->payment_detail;

        $leader = $user->getFirstLeader();
        if ($leader && $request->payment_method == 'payment_merchant') {
            $payment_gateway_ids = PaymentGatewayToLeader::where('user_id', $leader->id)
                ->pluck('payment_gateway_id')
                ->toArray();

            $payment_gateway = PaymentGateway::whereIn('id', $payment_gateway_ids)
                ->where([
                    'platform' => $payment_detail['platform'],
                    'payment_app_name' => $payment_detail['payment_app_name'],
                ])
                ->first();
        } else {
            $payment_gateway = null;
        }

        $amount = $request->amount;
        $latest_transaction = Transaction::where([
            'user_id' => $user->id,
            'category' => 'wallet',
            'transaction_type' => 'Deposit',
            'status' => 'Processing',
        ])
            ->latest()
            ->first();

        // Check processing transaction
        if (!empty($latest_transaction) && $latest_transaction->amount == $amount) {
            if ($latest_transaction->payment_method == 'Payment Merchant' && $request->payment_method == 'payment_merchant') {
                $transaction = $latest_transaction;
            } else {
                return back()->with('toast', [
                    'title' => trans("public.warning"),
                    'message' => trans('public.pending_deposit_caption'),
                    'type' => 'warning',
                ]);
            }
        } else {
            $transaction = Transaction::create([
                'category' => 'wallet',
                'user_id' => $user->id,
                'to_wallet_id' => $wallet->id,
                'payment_method' => $payment_detail['payment_method'] ?? 'Payment Merchant',
                'transaction_number' => RunningNumberService::getID('transaction'),
                'to_wallet_address' => $payment_detail['account_no'] ?? null,
                'transaction_type' => 'Deposit',
                'amount' => $amount,
                'transaction_charges' => 0,
                'conversion_rate' => $payment_detail['currency_rate'] ?? 0,
                'status' => 'Processing',
            ]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $transaction->addMedia($image)->toMediaCollection('receipt');
            }
        }

        if ($request->payment_method == 'payment_merchant' && $payment_gateway) {
            $transaction->update([
                'payment_gateway_id' => $request->setting_payment_id,
            ]);
            // Find available payment merchant
            $params = [];
            $baseUrl = '';
            switch ($payment_gateway->platform) {
                case 'ttpay':
                    $vCode = md5($amount . $payment_gateway->payment_app_name . $transaction->transaction_number . $payment_gateway->secondary_key . $payment_gateway->secret_key);
                    $params = [
                        'userName' => $user->name,
                        'userEmail' => $user->email,
                        'orderNumber' => $transaction->transaction_number,
                        'userId' => $user->id,
                        'amount' => $amount,
                        'merchantId' => $payment_gateway->secondary_key,
                        'vCode' => $vCode,
                        'token' => Str::random(40),
                        'locale' => app()->getLocale(),
                    ];
                    $baseUrl = $payment_gateway->payment_url . '/payment';
                    break;

                case 'spritpayment':
                    $intAmount = intval($amount * 100);
                    $vCode = md5($intAmount . $payment_gateway->payment_app_name . $transaction->transaction_number . $payment_gateway->secret_key);
                    $params = [
                        'amount' => $intAmount,
                        'orderNumber' => $transaction->transaction_number,
                        'userId' => $user->id,
                        'vCode' => $vCode,
                    ];
                    $baseUrl = $payment_gateway->payment_url;
                    break;
            }

            // Send response
            $redirectUrl = $baseUrl . "?" . http_build_query($params);

            return Inertia::location($redirectUrl);
        } else {
            $transaction->update([
                'setting_payment_method_id' => $request->setting_payment_id,
                'transaction_amount' => $amount * $payment_detail['currency_rate'] > 0 ? $amount * $payment_detail['currency_rate'] : $amount,
            ]);

            Notification::route('mail', 'sluckyant@gmail.com')
                ->notify(new DepositRequestNotification($transaction));
        }

        return redirect()->back()
            ->with('title', trans('public.success_request_deposit'))
            ->with('success', trans('public.successfully_request_deposit'));
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
            'walletAddress' => $data['walletAddress'] ?? null,
            'status' => $data['status'],
            'sCode' => $data['sCode'] ?? null,
            'transactionHash' => $data['transactionHash'] ?? null,
            'sourceAddress' => $data['sourceAddress'] ?? null,
            'blockTime' => $data['blockTime'] ?? null,
            'paidTime' => $data['paidTime'] ?? null,
            'receivedAmount' => $data['receivedAmount'] ?? null,
        ];
        $transaction = Transaction::where('user_id', $result['userId'])
            ->where('transaction_number', $result['orderNumber'])
            ->first();

        $selectedPaymentGateway = PaymentGateway::find($transaction->payment_gateway_id);

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway->payment_app_name . $selectedPaymentGateway->secondary_key);

        if($result['status'] == 'PENDING') {
            return to_route('dashboard');
        }

        if ($result['sCode'] == $sCode2) {
            $wallet = Wallet::find($transaction->to_wallet_id);

            if ($transaction->status == 'Processing') {
                if ($result['status'] == 'PAID') {
                    $transaction->update([
                        'amount' => $result['receivedAmount'] / 100,
                        'transaction_amount' => $result['receivedAmount'] / 100,
                        'txn_hash' => $result['transactionHash'],
                        'status' => 'Success'
                    ]);

                    $walletTotalBalance = $wallet->balance + $transaction->transaction_amount;
                    $wallet->update([
                        'balance' => $walletTotalBalance,
                    ]);

                    Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
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

        $result = [
            'amount' => $data['amount'],
            'userId' => $data['userId'],
            'vCode' => $data['vCode'],
            'orderNumber' => $data['orderNumber'],
            'transactionId' => $data['transactionId'],
            'walletAddress' => $data['walletAddress'] ?? null,
            'status' => $data['status'],
            'sCode' => $data['sCode'],
            'transactionHash' => $data['transactionHash'] ?? null,
            'sourceAddress' => $data['sourceAddress'] ?? null,
            'blockTime' => $data['blockTime'] ?? null,
            'paidTime' => $data['paidTime'] ?? null,
            'receivedAmount' => $data['receivedAmount'],
        ];

        $transaction = Transaction::where('user_id', $result['userId'])
            ->where('transaction_number', $result['orderNumber'])
            ->first();

        $selectedPaymentGateway = PaymentGateway::find($transaction->payment_gateway_id);

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway->payment_app_name . $selectedPaymentGateway->secondary_key);
        $transaction = Transaction::where('user_id', $result['userId'])->where('transaction_number', $result['orderNumber'])->first();

        if($result['status'] == 'EXPIRED') {
            $transaction->update([
                'status' => 'Rejected',
                'approval_at' => now()
            ]);
        }

        if ($result['sCode'] == $sCode2) {

            $wallet = Wallet::find($transaction->to_wallet_id);

            if ($transaction->status == 'Processing') {
                if ($result['status'] == 'PAID') {
                    $transaction->update([
                        'amount' => $result['receivedAmount'] / 100,
                        'transaction_amount' => $result['receivedAmount'] / 100,
                        'txn_hash' => $result['transactionHash'],
                        'status' => 'Success',
                        'approval_at' => now(),
                        'new_wallet_amount' => $wallet->balance + $result['receivedAmount'] / 100
                    ]);

                    $walletTotalBalance = $wallet->balance + $transaction->transaction_amount;
                    $wallet->update([
                        'balance' => $walletTotalBalance,
                    ]);

                    Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
                } else {
                    $transaction->update([
                        'txn_hash' => $result['transactionHash'],
                        'status' => 'Rejected'
                    ]);
                }
            }
        }
    }

    public function withdrawal(WithdrawalRequest $request)
    {
        $user = Auth::user();

        if ($user->tradingAccounts()->sum('demo_fund') > 0) {
            return back()->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans('public.not_allowed_to_withdraw'),
                'type' => 'warning',
            ]);
        }

        if (empty($request->withdraw_wallets)) {
            return back()->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans('public.insufficient_balance'),
                'type' => 'warning',
            ]);
        }

        if (!is_null($user->security_pin) && !Hash::check($request->get('security_pin'), $user->security_pin)) {
            return back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.current_pin_invalid'));
        }

        if ($user->password_changed_at !== null) {
            $passwordChangedTime = Carbon::parse($user->password_changed_at);
            $hoursDifference = $passwordChangedTime->diffInHours(Carbon::now());

            if ($hoursDifference < 24) {
                return back()
                    ->with('title', trans('public.invalid_action'))
                    ->with('warning', trans('public.password_change_restriction'));
            }
        }

        $payment_account = PaymentAccount::find($request->payment_account_id);
        $conversion_rate = CurrencyConversionRate::firstWhere('base_currency', $payment_account->currency);

        $withdraw_wallets = $request->withdraw_wallets;

        $total_wallet_balance = 0;
        $total_withdraw_amount = 0;
        foreach ($withdraw_wallets as $withdraw_wallet => $amount) {
            $wallet = Wallet::find($withdraw_wallet);

            if ($wallet->balance < $amount) {
                throw ValidationException::withMessages(['withdraw_wallets.' . $wallet->id => trans('public.insufficient_wallet_balance', ['wallet' => trans("public.$wallet->type")])]);
            }

            $total_wallet_balance += $wallet->balance;
            $total_withdraw_amount += $amount;
        }

        if ($total_withdraw_amount < 10) {
            throw ValidationException::withMessages(['amount' => trans('public.min_withdrawal_amount', ['amount' => '$10.00'])]);
        }

        if ($total_wallet_balance < $total_withdraw_amount) {
            return back()->with('toast', [
                'title' => trans("public.warning"),
                'message' => trans('public.insufficient_balance'),
                'type' => 'warning',
            ]);
        }

        $transaction_charges = $request->transaction_charges;
        $transaction_number = RunningNumberService::getID('transaction');

        $non_zero_wallets = array_filter($withdraw_wallets, fn($amount) => $amount > 0);

        if (count($non_zero_wallets) > 1) {
            $charge_per_wallet = $transaction_charges / count($non_zero_wallets);

            foreach ($withdraw_wallets as $withdraw_wallet => $amount) {
                if ($amount > 0) {
                    $wallet = Wallet::find($withdraw_wallet);
                    $partial_amount = $amount - $charge_per_wallet;
                    $conversion_amount = $partial_amount * $conversion_rate->withdrawal_rate;

                    Transaction::create([
                        'category' => 'wallet',
                        'user_id' => $user->id,
                        'from_wallet_id' => $wallet->id,
                        'transaction_number' => $transaction_number,
                        'payment_account_id' => $payment_account->id,
                        'payment_account_name' => $payment_account->payment_account_name,
                        'payment_method' => $payment_account->payment_platform,
                        'to_wallet_address' => $payment_account->account_no,
                        'transaction_type' => 'Withdrawal',
                        'amount' => $amount,
                        'conversion_rate' => $conversion_rate->withdrawal_rate,
                        'transaction_charges' => $charge_per_wallet,
                        'conversion_amount' => $conversion_amount,
                        'transaction_amount' => $partial_amount,
                        'new_wallet_amount' => $wallet->balance - $amount,
                        'status' => 'Processing',
                    ]);

                     $wallet->balance -= $amount;
                     $wallet->save();
                }
            }
        } else {
            foreach ($withdraw_wallets as $withdraw_wallet => $amount) {
                if ($amount > 0) {
                    $wallet = Wallet::find($withdraw_wallet);
                    $final_amount = $amount - $transaction_charges;
                    $conversion_amount = $final_amount * $conversion_rate->withdrawal_rate;

                    Transaction::create([
                        'category' => 'wallet',
                        'user_id' => $user->id,
                        'from_wallet_id' => $wallet->id,
                        'transaction_number' => $transaction_number,
                        'payment_account_id' => $payment_account->id,
                        'payment_method' => $payment_account->payment_platform,
                        'to_wallet_address' => $payment_account->account_no,
                        'transaction_type' => 'Withdrawal',
                        'amount' => $amount,
                        'conversion_rate' => $conversion_rate->withdrawal_rate,
                        'transaction_charges' => $transaction_charges,
                        'conversion_amount' => $conversion_amount,
                        'transaction_amount' => $final_amount,
                        'new_wallet_amount' => $wallet->balance - $amount,
                        'status' => 'Processing',
                    ]);

                    $wallet->balance -= $amount;
                    $wallet->save();
                }
            }
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans('public.successfully_submit_withdrawal_request'),
            'type' => 'success',
        ]);
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

        if ($from_wallet->user_id != $user->id || $to_wallet->user_id != $user->id) {
            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.try_again_later'));
        }

        // Check if balance is sufficient
        if ($from_wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_wallet_balance', ['wallet' => $from_wallet->name])]);
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

    public function transfer(TransferRequest $request)
    {
        $user = \Auth::user();
        $from_wallet = Wallet::where('id', $request->from_wallet)->first();
        $to_wallet = Wallet::where('wallet_address', $request->wallet_address)->first();
        $to_user = $to_wallet->user;
        $amount = $request->amount;
        // Get IDs from hierarchy lists
        $userHierarchyIDs = explode('-', trim($user->hierarchyList, '-'));
        $toUserHierarchyIDs = explode('-', trim($to_user->hierarchyList, '-'));
        // Validate if to_user's ID is in user's hierarchy list
        $isToUserInUserHierarchy = in_array($to_user->id, $userHierarchyIDs);

        // Validate if user's ID is in to_user's hierarchy list
        $isUserInToUserHierarchy = in_array($user->id, $toUserHierarchyIDs);

        // Check if the to_wallet exists and is of type 'e_wallet'
        if (!$to_wallet || $to_wallet->type !== 'e_wallet') {
            throw ValidationException::withMessages([
                'wallet_address' => trans('public.invalid_transfer_wallet_type'),
            ]);
        }

        if ($from_wallet->id == $to_wallet->id) {
            throw ValidationException::withMessages([
                'wallet_address' => trans('public.same_wallet_address_error'),
            ]);
        }

        // Check if to_user's ID is in user's hierarchy list or vice versa
        if (!$isToUserInUserHierarchy && !$isUserInToUserHierarchy) {
            throw ValidationException::withMessages(['wallet_address' => trans('public.hierarchy_validation_error')]);
        }

        // Check if balance is sufficient
        if ($from_wallet->balance < $amount || $amount <= 0) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_wallet_balance', ['wallet' => $from_wallet->name])]);
        }

        $transaction_number = RunningNumberService::getID('transaction');

        // Create transaction
        $transaction = Transaction::create([
            'category' => 'wallet',
            'user_id' => $user->id,
            'from_wallet_id' => $from_wallet->id,
            'to_wallet_id' => $to_wallet->id,
            'transaction_number' => $transaction_number,
            'transaction_type' => 'Transfer',
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

        \Notification::send($to_user, new TransferNotification($amount, $from_wallet, $to_wallet));


        return redirect()->back()
            ->with('title', trans('public.success_transfer_transaction'))
            ->with('success', trans('public.successfully_transfer') . ' $' . number_format($amount, 2) . ' ' . trans('public.from_wallet') . ': ' . $from_wallet->wallet_address . ' ' . trans('public.to_wallet') . ': ' . $to_wallet->wallet_address);
    }

    public function wallet_history()
    {
        return Inertia::render('Wallet/WalletHistory', [
            'wallets' => Wallet::where('user_id', \Auth::id())->get(),
            'transactionTypeSel' => (new SelectOptionService())->getTransactionType(),
            'walletsSel' => (new SelectOptionService())->getAllWallets(),
        ]);
    }

    public function getWalletHistories(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        // Get the IDs of the user's wallets
        $userId = Auth::id();

        // Get the IDs of the user's wallets
        $walletIds = Wallet::where('user_id', $userId)
            ->whereIn('type', ['cash_wallet', 'bonus_wallet', 'e_wallet'])
            ->pluck('id');

        // Retrieve individual wallet IDs for each type
        $cashWalletId = Wallet::where('user_id', $userId)->where('type', 'cash_wallet')->value('id');
        $bonusWalletId = Wallet::where('user_id', $userId)->where('type', 'bonus_wallet')->value('id');
        $eWalletId = Wallet::where('user_id', $userId)->where('type', 'e_wallet')->value('id');

        // Base query for wallet histories
        $walletHistoriesQuery = Transaction::with([
            'from_wallet.user',
            'to_wallet.user',
            'from_meta_login',
            'to_meta_login',
            'payment_account'
        ])
            ->where(function ($query) use ($walletIds) {
                $query->whereIn('from_wallet_id', $walletIds)
                    ->orWhereIn('to_wallet_id', $walletIds);
            })
            ->whereIn('category', ['wallet', 'trading_account']);

        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $walletHistoriesQuery->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', $search)
                    ->orWhere('amount', 'like', $search);
            });
        }

        // Apply transaction type filter if provided
        if ($request->filled('type')) {
            $type = $request->input('type');
            $walletHistoriesQuery->where('transaction_type', $type);
        }

        // Apply date range filter if provided
        if ($request->filled('date')) {
            $dateRange = explode(' - ', $request->input('date'));
            $startDate = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
            $walletHistoriesQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Clone the query for amount calculations
        $amountQuery = clone $walletHistoriesQuery;

        // Function to calculate wallet amounts
        function calculateWalletAmounts($walletId, $amountQuery) {
            // Calculate the amount received by the wallet
            $amountTo = (clone $amountQuery)->where('to_wallet_id', $walletId)
                ->whereNot('status', 'Rejected')
                ->whereNotIn('transaction_type', ['WalletAdjustment'])
                ->sum('amount');

            // Calculate the positive adjustments received by the wallet
            $amountAdjustmentTo = (clone $amountQuery)->where('from_wallet_id', $walletId)
                ->whereNot('status', 'Rejected')
                ->where('transaction_type', 'WalletAdjustment')
                ->sum('amount');

            // Calculate the amount sent from the wallet
            $amountFrom = (clone $amountQuery)->where('from_wallet_id', $walletId)
                ->whereNot('status', 'Rejected')
                ->whereNotIn('transaction_type', ['WalletAdjustment'])
                ->sum('amount');

            // Calculate the positive adjustments sent from the wallet
            $amountAdjustmentFrom = (clone $amountQuery)->where('to_wallet_id', $walletId)
                ->whereNot('status', 'Rejected')
                ->where('transaction_type', 'WalletAdjustment')
                ->sum('amount');
            // Return both amounts
            return [
                'to' => $amountTo + $amountAdjustmentTo,
                'from' => $amountFrom + $amountAdjustmentFrom
            ];
        }

        // Calculate wallet amounts separately
        $cashWalletAmounts = calculateWalletAmounts($cashWalletId, $amountQuery);
        $bonusWalletAmounts = calculateWalletAmounts($bonusWalletId, $amountQuery);
        $eWalletAmounts = calculateWalletAmounts($eWalletId, $amountQuery);

        // Apply additional wallet ID filter if provided
        if ($request->filled('wallet_id')) {
            $walletId = $request->input('wallet_id');
            $walletHistoriesQuery->where(function ($query) use ($walletId) {
                $query->where('from_wallet_id', $walletId)
                    ->orWhere('to_wallet_id', $walletId);
            });
        }

        // Retrieve the results with ordering and pagination
        $results = $walletHistoriesQuery
            ->orderBy($column ?? 'created_at', $sortOrder)
            ->paginate($request->input('paginate', 10));

        $cashWalletAmountTo = $cashWalletAmounts['to'];
        $cashWalletAmountFrom = $cashWalletAmounts['from'];

        $bonusWalletAmountTo = $bonusWalletAmounts['to'];
        $bonusWalletAmountFrom = $bonusWalletAmounts['from'];

        $eWalletAmountTo = $eWalletAmounts['to'];
        $eWalletAmountFrom = $eWalletAmounts['from'];

        return response()->json([
            'walletHistories' => $results,
            'cashWalletAmount' => $cashWalletAmountTo - $cashWalletAmountFrom,
            'bonusWalletAmount' => $bonusWalletAmountTo - $bonusWalletAmountFrom,
            'ewalletAmount' => $eWalletAmountTo - $eWalletAmountFrom,
        ]);
    }

    public function tt_pay_return()
    {
        return to_route('dashboard');
    }

    public function tt_pay_callback(Request $request)
    {
        $data = $request->all();

        $result = [
            "token" => $data['vCode'],
            "from_wallet_address" => $data['from_wallet'],
            "to_wallet_address" => $data['to_wallet'],
            "txn_hash" => $data['txID'],
            "transaction_number" => $data['transaction_number'],
            "amount" => $data['transfer_amount'],
            "transfer_amount_type" => $data['transfer_amount_type'],
            "status" => $data["status"],
            "remarks" => 'System Approval',
        ];

        $transaction = Transaction::query()
            ->where('transaction_number', $result['transaction_number'])
            ->first();

        $selectedPayout = PaymentGateway::find($transaction->payment_gateway_id);

        $dataToHash = md5($transaction->transaction_number . $selectedPayout->payment_app_name . $selectedPayout->secondary_key);
        $status = $result['status'] == 'success' ? 'Success' : 'Rejected';

        if ($result['token'] === $dataToHash) {
            $transaction->update([
                'to_wallet_address' => $result['to_wallet_address'],
                'txn_hash' => $result['txn_hash'],
                'transaction_charges' => 0,
                'status' => $status,
                'remarks' => $result['remarks'],
                'approved_at' => now()
            ]);

            if ($result['transfer_amount_type'] == 'invalid') {
                $transaction->update([
                    'transaction_amount' => $result['amount'],
                    'status' => 'Processing',
                ]);
            } else {
                $transaction->update([
                    'amount' => $result['amount'],
                    'transaction_amount' => $result['amount'],
                    'status' => $status,
                    'remarks' => $result['remarks'],
                    'approved_at' => now()
                ]);
            }

            if ($transaction->status == 'Success') {
                if ($transaction->transaction_type == 'Deposit') {
                    $wallet = Wallet::find($transaction->to_wallet_id);
                    $wallet->balance += $result['amount'];
                    $wallet->save();

                    $transaction->update([
                        'new_wallet_amount' => $wallet->balance
                    ]);

                    Notification::route('mail', $transaction->user->email)->notify(new DepositConfirmationNotification($transaction));
                }
            }
        }

        return response()->json(['success' => false, 'message' => 'Deposit Failed']);
    }
}
