<?php

namespace App\Http\Controllers;

use App\Exports\TransactionExport;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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
}
