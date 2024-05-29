<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Exports\TransactionExport;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\SelectOptionService;

class TransactionController extends Controller
{
    public function index()
    {
        return Inertia::render('Transaction/Transaction', [
            'transactionTypes' => (new SelectOptionService())->getTradingAccountTransactionTypes(),
        ]);
    }

    public function getTransactionData(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = Transaction::with([
            'from_meta_login:id,meta_login',
            'to_meta_login:id,meta_login',
            'from_wallet',
            'to_wallet'
        ])
            ->where('user_id', \Auth::id())
            ->where('category', 'trading_account')
            ->whereNot('transaction_type', 'Settlement');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($query) use ($search) {
                $query->whereHas('from_meta_login', function ($subQuery) use ($search) {
                    $subQuery->where('meta_login', 'like', $search);
                })->orWhereHas('to_meta_login', function ($subQuery) use ($search) {
                    $subQuery->where('meta_login', 'like', $search);
                });
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $totalBalanceIn = clone $query;
        $totalBalanceOut = clone $totalBalanceIn;

        if ($request->filled('type')) {
            $type = $request->input('type');
            $query->where('transaction_type', $type);
        }

        $results = $query
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'transactionHistories' => $results,
            'totalBalanceIn' => $totalBalanceIn->where('transaction_type', 'BalanceIn')->where('status', 'Success')->sum('transaction_amount'),
            'totalBalanceOut' => $totalBalanceOut->where('transaction_type', 'BalanceOut')->where('status', 'Success')->sum('transaction_amount'),
        ]);
    }

    public function transfer_history()
    {
        return Inertia::render('Transaction/TransferHistory/TransferHistory',);
    }

    public function getTransferHistory(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $userId = Auth::id();
        $eWalletID = Wallet::where('user_id', $userId)->where('type', 'e_wallet')->value('id');

        $transfer = Transaction::with('from_wallet.user', 'to_wallet.user')
                ->where(function ($query) use ($userId) {
                    $query->whereIn('to_wallet_id', function ($subQuery) use ($userId) {
                        $subQuery->select('id')
                            ->from('wallets')
                            ->where('user_id', $userId);
                    })->orWhereIn('from_wallet_id', function ($subQuery) use ($userId) {
                        $subQuery->select('id')
                            ->from('wallets')
                            ->where('user_id', $userId);
                    });
                })
                ->where('transaction_type', 'Transfer');


        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $transfer->where(function ($q) use ($search) {
                $q->where('amount', 'like', $search)
                    ->orWhere('transaction_number', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $transfer->where('to_wallet_id', $type);
        }

                // if ($request->filled('wallet_id')) {
        //     $wallet_id = $request->input('wallet_id');
        //     $transfer->whereHas('wallet', function ($query) use ($wallet_id) {
        //         $query->where('id', $wallet_id);
        //     });
        // }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $transfer->whereBetween('created_at', [$start_date, $end_date]);
        }

        $results = $transfer
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'transfer' => $results,
            'totalTransfer' => $results->count(),
            'totalTranferOutAmount' => $results->where('from_wallet_id', $eWalletID)->sum('amount'),
            'totalTranferInAmount' => $results->where('to_wallet_id', $eWalletID)->sum('amount'),
        ]);
    }

}
