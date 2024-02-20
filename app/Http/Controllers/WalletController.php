<?php

namespace App\Http\Controllers;

use App\Services\RunningNumberService;
use App\Services\SelectOptionService;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;
use Auth;

class WalletController extends Controller
{
    public function wallet()
    {

        $user = Auth::user();

        $wallets = Wallet::where('user_id', $user->id)->get();

        return Inertia::render('Transaction/Wallet/Wallet', [
            'wallets' => $wallets,
            'walletSel' => (new SelectOptionService())->getWalletSelection(),
        ]);
    }

    public function getWalletHistory(Request $request)
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
        ]);

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

    public function deposit(Request $request)
    {
        $user = Auth::user();
        $wallet = Wallet::find($request->wallet_id);
        $amount = number_format(floatval($request->amount), 2, '.', '');

        $transaction_number = RunningNumberService::getID('transaction');

        $transaction = Transaction::create([
            'category' => 'wallet',
            'user_id' => $user->id,
            'to_wallet_id' => $wallet->id,
            'transaction_number' => $transaction_number,
            'transaction_type' => 'Deposit',
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'status' => 'Processing',
        ]);

        $paymentGateway = config('payment-gateway');
        $intAmount = intval($amount * 100);

        // vCode
        $vCode = md5($intAmount . $paymentGateway['staging']['appId'] . $transaction_number . $paymentGateway['staging']['vKey']);

        $params = [
            'amount' => $intAmount,
            'orderNumber' => $transaction_number,
            'userId' => $user->id,
            'vCode' => $vCode,
        ];

        // Send response
        $baseUrl = $paymentGateway['staging']['paymentUrl'];
        $redirectUrl = $baseUrl . "?" . http_build_query($params);

        return Inertia::location($redirectUrl);
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

        $paymentGateway = config('payment-gateway');
        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $paymentGateway['staging']['appId'] . $paymentGateway['staging']['sKey']);

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

        $paymentGateway = config('payment-gateway');
        $sCode1 = md5($result['transactionId'] . $result['orderNumber'] . $result['status'] . $result['amount']);
        $sCode2 = md5($result['walletAddress'] . $sCode1 . $paymentGateway['staging']['appId'] . $paymentGateway['staging']['sKey']);

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
}
