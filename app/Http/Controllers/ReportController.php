<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\TradeRebateSummary;

class ReportController extends Controller
{
    public function tradeRebate()
    {
        $locale = app()->getLocale();

        $tradeRebatehistories = TradeRebateSummary::where('upline_user_id', auth()->user()->id)
            ->where('status', 'Approved')
            ->get();

        $totalRebate = $tradeRebatehistories->sum('rebate');
        $totalVolume = $tradeRebatehistories->sum('volume');
        return Inertia::render('Report/TradeRebate/TradeRebateHistory', [
            'totalRebate' => $totalRebate,
            'totalVolume' => $totalVolume,
        ]);
    }

    public function getTradeRebateHistories(Request $request)
    {
        $locale = app()->getLocale();
        $childrenIds = \Auth::user()->getChildrenIds();

        $tradeRebatehistories = TradeRebateSummary::with('ofUser', 'tradingAccount.tradingUser')
            ->where('upline_user_id', auth()->user()->id)
            ->where('status', 'Approved')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->where(function ($query) use ($search) {
                    $query->where('meta_login', 'like', $search)
                            ->orWhere('rebate', 'like', $search)
                            ->orWhere('volume', 'like', $search)
                        ->orWhereHas('ofUser', function ($subQuery) use ($search) {
                            $subQuery->where('username', 'like', $search)
                                    ->orWhere('email', 'like', $search);
                        })
                        ->orWhereHas('tradingAccount.tradingUser', function ($subQuery) use ($search) {
                            $subQuery->where('name', 'like', $search);
                        });        
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('type'), function ($query) use ($request, $childrenIds) {
                if ($request->type === 'Affiliate') {
                    $query->whereIn('user_id', $childrenIds);
                } elseif ($request->type === 'Personal') {
                    $query->where('user_id', \Auth::id());
                }
            })
            ->when($request->filled('sortType'), function($query) use ($request) {
                $sortType = $request->input('sortType');
                $sort = $request->input('sort');

                $query->orderBy($sortType, $sort);
            })
            ->latest()
            ->paginate(10);

        return response()->json($tradeRebatehistories);
    }
}