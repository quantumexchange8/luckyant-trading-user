<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
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
            $appUrl = parse_url(config('app.url'), PHP_URL_HOST);
            $paymentGateway = config('payment-gateway');
            $intAmount = intval($amount * 100);

            if ($domain === 'testmember.luckyantfxasia.com') {
                $selectedPaymentGateway = $paymentGateway['staging'];
            } elseif ($domain === $appUrl) {
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
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);

        if ($domain === 'testmember.luckyantfxasia.com') {
            $selectedPaymentGateway = $paymentGateway['staging'];
        } elseif ($domain === $appUrl) {
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
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);

        if ($domain === 'testmember.luckyantfxasia.com') {
            $selectedPaymentGateway = $paymentGateway['staging'];
        } elseif ($domain === $appUrl) {
            $selectedPaymentGateway = $paymentGateway['live'];
        } else {
            $selectedPaymentGateway = $paymentGateway['staging'];
        }

        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $selectedPaymentGateway['appId'] . $selectedPaymentGateway['sKey']);
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

        $tradingAccountDemoFunds = TradingAccount::where('user_id', $user->id)->sum('demo_fund');

        if ($tradingAccountDemoFunds > 0) {
            return back()
                ->with('title', trans('public.invalid_action'))
                ->with('warning', trans('public.not_allowed_to_withdraw'));
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

}
