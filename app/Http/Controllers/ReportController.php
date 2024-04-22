<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\WalletLog;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use Illuminate\Support\Carbon;
use App\Models\TradeRebateSummary;
use App\Services\SelectOptionService;

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

    public function wallet_history()
    {
        return Inertia::render('Report/WalletHistory/WalletHistory', [
            'walletsSel' => (new SelectOptionService())->getAllWallets(),
            'bonusTypeSel' => (new SelectOptionService())->getBonusType(),
            'wallets' => Wallet::where('user_id', \Auth::id())->whereNot('type', 'cash_wallet')->get()
        ]);
    }

    public function getWalletLogs(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $walletLogs = WalletLog::with('wallet')
            ->where('user_id', \Auth::id())
            ->where('category', 'bonus');

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $walletLogs->where(function ($q) use ($search) {
                $q->whereHas('wallet', function ($user) use ($search) {
                    $user->where('name', 'like', $search);
                })
                    ->orWhere('amount', 'like', $search);
            });
        }

        if ($request->filled('type')) {
            $type = $request->input('type');
            $walletLogs->where('purpose', $type);
        }

        if ($request->filled('wallet_id')) {
            $wallet_id = $request->input('wallet_id');
            $walletLogs->whereHas('wallet', function ($query) use ($wallet_id) {
                $query->where('id', $wallet_id);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $walletLogs->whereBetween('created_at', [$start_date, $end_date]);
        }

        $totalBonus = $walletLogs->sum('amount');

        $results = $walletLogs
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'walletLogs' => $results,
            'totalBonus' => $totalBonus,
        ]);
    }

    public function trade_history()
    {    
        return Inertia::render('Report/TradeHistory/TradeHistory', [
            'tradingAccounts' => (new SelectOptionService())->getTradingAccounts(),
        ]);
    }

    public function getTradeHistories(Request $request)
    {    
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);
    
        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';
        
        $tradingAccountExists = TradingAccount::where('user_id', \Auth::id())
            ->where('meta_login', $request->input('meta_login'))
            ->exists();

        if ($tradingAccountExists) {
            $tradeHistories = TradeHistory::query();
        
            if ($request->filled('meta_login')) {
                $metaLogin = $request->input('meta_login');
                $tradeHistories->where('meta_login', $metaLogin);
            }
                
            if ($request->filled('date')) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $tradeHistories->whereBetween('time_close', [$start_date, $end_date]);
            }
        
            if ($request->filled('type')) {
                $type = $request->input('type');
                $types = explode(',', $type); // Convert comma-separated string to array
                $tradeHistories->whereIn('symbol', $types);
            }
        
            if ($request->filled('tradeType')) {
                $tradeType = $request->input('tradeType');
                $tradeHistories->where('trade_type', $tradeType);
            }
        
            $totalProfit = $tradeHistories->where('trade_status', 'Closed')->sum('trade_profit');
            $totalTradeLot = $tradeHistories->where('trade_status', 'Closed')->sum('volume');
            // Apply sorting and pagination
            $tradeHistories = $tradeHistories->where('trade_status', 'Closed')
                ->orderBy($column, $sortOrder)
                ->paginate($request->input('paginate', 10));
        
                return response()->json([
                    'tradeHistories' => $tradeHistories,
                    'totalProfit' => $totalProfit,
                    'totalTradeLot' => $totalTradeLot,
                ]);
        }
    }
}
