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
        return Inertia::render('Transaction/Transaction');
    }

    public function getTransactionData(Request $request)
    {
        $transactions = Transaction::query()
            ->where('user_id', Auth::id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('transaction_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                $query->where(function ($innerQuery) use ($type) {
                    $innerQuery->where('transaction_type', $type);
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $category = $request->input('category');
                $query->where(function ($innerQuery) use ($category) {
                    $innerQuery->where('category', $category);
                });
            })
            ->when($request->filled('methods'), function ($query) use ($request) {
                $methods = $request->input('methods');
                $query->where(function ($innerQuery) use ($methods) {
                    $innerQuery->where('payment_method', $methods);
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('sortType'), function($query) use ($request) {
                $sortType = $request->input('sortType');
                $sort = $request->input('sort');

                $query->orderBy($sortType, $sort);
            })
            ->with(['to_wallet:id,name,type', 'from_wallet:id,name,type', 'to_meta_login:id,meta_login', 'from_meta_login:id,meta_login', 'payment_account'])
            ->latest();

        if ($request->has('exportStatus')) {
            return Excel::download(new TransactionExport($transactions), Carbon::now() . '-' . $request->type . '-report.xlsx');
        }

        $transactions = $transactions->paginate(10);

        return response()->json($transactions);
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
