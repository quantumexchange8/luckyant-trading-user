<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        return Inertia::render('Transaction/Transaction');
    }

    public function getTransactionData(Request $request, $category)
    {
        $payments = Payment::query()
            ->where('user_id', Auth::id())
            ->where('category', $category)
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $start_date = Carbon::createFromFormat('Y-m-d', $date[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $date[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('to', 'like', '%' . $search . '%')
                    ->orWhere('from', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(10);

        return response()->json($payments);
    }
}
