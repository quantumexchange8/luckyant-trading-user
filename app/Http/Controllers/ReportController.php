<?php

namespace App\Http\Controllers;

use App\Models\PerformanceIncentive;
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
        $rebateTypes = [
            ['value' => 'affiliate', 'label' => trans('public.affiliate')],
            ['value' => 'personal', 'label' => trans('public.personal')],
        ];

        return Inertia::render('Report/TradeRebate/TradeRebateHistory', [
            'rebateTypes' => $rebateTypes
        ]);
    }

    public function getTradeRebateHistories(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = TradeRebateSummary::with(['ofUser', 'tradingAccount.tradingUser'])
            ->where('upline_user_id', \Auth::id())
            ->where('status', 'Approved');

        if ($request->filled('search')) {
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
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $totalRebateQuery = clone $query;
        $totalAffiliateQuery = clone $totalRebateQuery;
        $totalPersonalQuery = clone $totalRebateQuery;

        if ($request->filled('type')) {
            if ($request->type == 'affiliate') {
                $childrenIds = \Auth::user()->getChildrenIds();
                $query->whereIn('user_id', $childrenIds);
            } elseif ($request->type == 'personal') {
                $query->where('user_id', \Auth::id());
            }
        }

        $results = $query
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'tradeRebates' => $results,
            'totalRebateAmount' => $totalRebateQuery->sum('rebate'),
            'totalAffiliateRebate' => $totalAffiliateQuery->whereIn('user_id', \Auth::user()->getChildrenIds())->whereNot('user_id', \Auth::id())->sum('rebate'),
            'totalPersonalRebate' => $totalPersonalQuery->where('user_id', \Auth::id())->sum('rebate'),
            'totalTradeLots' => $totalRebateQuery->sum('volume'),
        ]);
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
            ->where('category', 'bonus')
            ->whereNot('purpose', 'ProfitSharing');

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

        $bonusRewardAmountQuery = clone $walletLogs;
        $rewardAmountQuery = clone $walletLogs;
        $totalBonus = $walletLogs->sum('amount');

        $results = $walletLogs
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'walletLogs' => $results,
            'totalBonus' => $totalBonus,
            'bonusAmount' => $bonusRewardAmountQuery->where('wallet_type', 'bonus_wallet')->sum('amount'),
            'ewalletAmount' => $rewardAmountQuery->where('wallet_type', 'e_wallet')->sum('amount'),
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

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'time_close';
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

    public function performance_incentive()
    {
        $rebateTypes = [
            ['value' => 'affiliate', 'label' => trans('public.affiliate')],
            ['value' => 'personal', 'label' => trans('public.personal')],
        ];

        return Inertia::render('Report/PerformanceIncentive/PerformanceIncentive', [
            'rebateTypes' => $rebateTypes
        ]);
    }

    public function getPerformanceIncentive(Request $request)
    {
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = PerformanceIncentive::with('user')
            ->where('user_id', \Auth::id());

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search)
                            ->orWhere('username', 'like', $search);
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

        $totalPerformanceQuery = clone $query;
        $totalAffiliateQuery = clone $totalPerformanceQuery;
        $totalPersonalQuery = clone $totalPerformanceQuery;
        $meta_logins = TradingAccount::query()->where('user_id', \Auth::id())->get()->pluck('meta_login')->toArray();

        if ($request->filled('type')) {
            if ($request->type == 'affiliate') {
                $childrenIds = \Auth::user()->getChildrenIds();
                $query->whereNull('meta_login');
            } elseif ($request->type == 'personal') {
                $query->whereIn('meta_login', $meta_logins);
            }
        }

        $results = $query
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        return response()->json([
            'performanceIncentives' => $results,
            'totalPerformanceIncentive' => $totalPerformanceQuery->sum('personal_bonus_amt'),
            'totalAffiliateAmount' => $totalAffiliateQuery->whereNull('meta_login')->sum('personal_bonus_amt'),
            'totalPersonalAmount' => $totalPersonalQuery->whereIn('meta_login', $meta_logins)->sum('personal_bonus_amt'),
        ]);
    }
}
