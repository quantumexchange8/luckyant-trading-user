<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        $amount = number_format(floatval($request->amount), 2, '.', '');
        $latest_transaction = Transaction::where('user_id', $user->id)
            ->where('category', 'wallet')
            ->where('transaction_type', 'Deposit')
            ->where('status', 'Processing')
            ->latest()
            ->first();

        // Check if a latest transaction exists and its created_at time is within the last 30 seconds
        if ($latest_transaction && Carbon::parse($latest_transaction->created_at)->diffInSeconds(Carbon::now()) < 30) {

            $remainingSeconds = 30 - Carbon::parse($latest_transaction->created_at)->diffInSeconds(Carbon::now());

            return redirect()->back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.please_wait_for_seconds', ['seconds' => $remainingSeconds]));
        }

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

        $domain = $_SERVER['HTTP_HOST'];
        $paymentGateway = config('payment-gateway');

        if ($domain === 'member.luckyantfxasia.com') {
            $selectedPaymentGateway = $paymentGateway['live'];
        } else {
            $selectedPaymentGateway = $paymentGateway['staging'];
        }

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway['appId'] . $selectedPaymentGateway['sKey']);

        if($result['status'] == 'PENDING') {
            return to_route('dashboard');
        }

        if ($result['sCode'] == $sCode2) {
            $transaction = Transaction::where('user_id', $result['userId'])->where('transaction_number', $result['orderNumber'])->first();
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

        Log::debug($data);

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

        $domain = $_SERVER['HTTP_HOST'];
        $paymentGateway = config('payment-gateway');

        if ($domain === 'member.luckyantfxasia.com') {
            $selectedPaymentGateway = $paymentGateway['live'];
        } else {
            $selectedPaymentGateway = $paymentGateway['staging'];
        }

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway['appId'] . $selectedPaymentGateway['sKey']);
        $transaction = Transaction::where('user_id', $result['userId'])->where('transaction_number', $result['orderNumber'])->first();

        if($result['status'] == 'EXPIRED') {
            $transaction->update([
                'status' => 'Rejected'
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
        $user = \Auth::user();
        $amount = number_format(floatval($request->amount), 2, '.', '');
        $paymentAccount = PaymentAccount::find($request->wallet_address);
        $conversion_rate = CurrencyConversionRate::where('base_currency', $paymentAccount->currency)->first();

        if (!is_null($user->security_pin) && !Hash::check($request->get('security_pin'), $user->security_pin)) {
            return back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.current_pin_invalid'));
        }

        $wallet = Wallet::find($request->wallet_id);

        $currentTime = Carbon::now();
        
        if ($user->password_changed_at !== null) {
            $passwordChangedTime = Carbon::parse($user->password_changed_at);
            $hoursDifference = $passwordChangedTime->diffInHours($currentTime);
        
            if ($hoursDifference < 24) {
                return back()
                    ->with('title', trans('public.invalid_action'))
                    ->with('warning', trans('public.password_change_restriction'));
            }
        }
        
        if ($wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_wallet_balance', ['wallet' => $wallet->name])]);
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

        return redirect()->back()->with('title', trans('public.success_submit_withdrawal_request'))->with('success', trans('public.successfully_submit_withdrawal_request'));
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
        $cashWalletId = Wallet::where('user_id', \Auth::id())->where('type', 'cash_wallet')->value('id');
        $bonusWalletId = Wallet::where('user_id', \Auth::id())->where('type', 'bonus_wallet')->value('id');
        $eWalletId = Wallet::where('user_id', \Auth::id())->where('type', 'e_wallet')->value('id');

        $walletHistories = Transaction::with('from_wallet.user', 'to_wallet.user', 'from_meta_login', 'to_meta_login', 'payment_account')
            ->where(function($query) use ($cashWalletId, $bonusWalletId, $eWalletId) {
                $query->where(function($q) use ($cashWalletId, $bonusWalletId, $eWalletId) {
                        $q->whereNotNull('from_wallet_id')
                            ->whereIn('from_wallet_id', [$cashWalletId, $bonusWalletId, $eWalletId]);
                    })
                    ->orWhere(function($q) use ($cashWalletId, $bonusWalletId, $eWalletId) {
                        $q->whereNotNull('to_wallet_id')
                            ->whereIn('to_wallet_id', [$cashWalletId, $bonusWalletId, $eWalletId]);
                    });
            });
        
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $walletHistories->where(function ($query) use ($search) {
                $query->where('transaction_number', 'like', $search)
                    ->orWhere('amount', 'like', $search);
            });
        }
        
        if ($request->filled('type')) {
            $type = $request->input('type');
            $walletHistories->where('transaction_type', $type);
        }
        
        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
            $walletHistories->whereBetween('created_at', [$start_date, $end_date]);
        }
        
        // Perform the query to retrieve wallet histories
        $amountQuery = $walletHistories->get();
        
        // Clone the query builder for calculating wallet amounts
        $cashWalletAmountQuery = clone $amountQuery;
        $bonusWalletAmountQuery = clone $amountQuery;
        $eWalletAmountQuery = clone $amountQuery;
        
        // Apply conditions to the cloned query builders
        $cashWalletAmountTo = $cashWalletAmountQuery->where('to_wallet_id', $cashWalletId)->whereNotIn('transaction_type', ['WalletAdjustment'])->sum('amount');
        $cashWalletAmountFrom = $cashWalletAmountQuery->where('from_wallet_id', $cashWalletId)->whereNotIn('transaction_type', ['WalletAdjustment'])->sum('amount');
        
        $bonusWalletAmountTo = $bonusWalletAmountQuery->where('to_wallet_id', $bonusWalletId)->whereNotIn('transaction_type', ['WalletAdjustment'])->sum('amount');
        $bonusWalletAmountFrom = $bonusWalletAmountQuery->where('from_wallet_id', $bonusWalletId)->whereNotIn('transaction_type', ['WalletAdjustment'])->sum('amount');
        
        $eWalletAmountTo = $eWalletAmountQuery->where('to_wallet_id', $eWalletId)->whereNotIn('transaction_type', ['WalletAdjustment'])->sum('amount');
        $eWalletAmountFrom = $eWalletAmountQuery->where('from_wallet_id', $eWalletId)->whereNotIn('transaction_type', ['WalletAdjustment'])->sum('amount');

        // WalletAdjusment condition
        $cashWalletAmountTo += $cashWalletAmountQuery->where('from_wallet_id', $cashWalletId)->where('transaction_type', 'WalletAdjustment')->where('amount', '>', 0)->sum('amount');
        $cashWalletAmount = $cashWalletAmountQuery->where('from_wallet_id', $cashWalletId)->where('transaction_type', 'WalletAdjustment')->where('amount', '<', 0)->pluck('amount');
    
        $cashWalletAmountFrom += $cashWalletAmount->map(function ($amount) {
            return abs($amount);
        })->sum();
    
        $bonusWalletAmountTo += $bonusWalletAmountQuery->where('from_wallet_id', $bonusWalletId)->where('transaction_type', 'WalletAdjustment')->where('amount', '>', 0)->sum('amount');
        $bonusWalletAmounts = $bonusWalletAmountQuery->where('from_wallet_id', $bonusWalletId)->where('transaction_type', 'WalletAdjustment')->where('amount', '<', 0)->pluck('amount');
    
        $bonusWalletAmountFrom += $bonusWalletAmounts->map(function ($amount) {
            return abs($amount);
        })->sum();

        $eWalletAmountTo += $eWalletAmountQuery->where('from_wallet_id', $eWalletId)->where('transaction_type', 'WalletAdjustment')->where('amount', '>', 0)->sum('amount');
        $eWalletAmounts = $eWalletAmountQuery->where('from_wallet_id', $eWalletId)->where('transaction_type', 'WalletAdjustment')->where('amount', '<', 0)->pluck('amount');
    
        $eWalletAmountFrom += $eWalletAmounts->map(function ($amount) {
            return abs($amount);
        })->sum();
        
        if ($request->filled('wallet_id')) {
            $wallet_id = $request->input('wallet_id');
            $walletHistories->where(function ($query) use ($wallet_id) {
                $query->where('from_wallet_id', $wallet_id)
                    ->orWhere('to_wallet_id', $wallet_id);
            });
        }

        $results = $walletHistories
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));
    
        return response()->json([
            'walletHistories' => $results,
            'cashWalletAmount' => $cashWalletAmountTo - $cashWalletAmountFrom,
            'bonusWalletAmount' => $bonusWalletAmountTo - $bonusWalletAmountFrom,
            'ewalletAmount' => $eWalletAmountTo - $eWalletAmountFrom,
        ]);
    }    

}
