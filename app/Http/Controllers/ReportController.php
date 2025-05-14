<?php

namespace App\Http\Controllers;

use App\Models\PerformanceIncentive;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = TradeRebateSummary::with([
                'ofUser',
                'tradingAccount'
            ])
                ->where([
                    'upline_user_id' => Auth::id(),
                    'status' => 'Approved'
                ]);

            if ($data['filters']['global']['value']) {
                $query->whereHas('ofUser', function($q) use ($data) {
                    $keyword = $data['filters']['global']['value'];
                    $q->where(function ($query) use ($keyword) {
                        $query->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('username', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })
                        ->orWhere('upline_meta_login', 'like', '%' . $keyword . '%')
                        ->orWhere('meta_login', 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            // Calculate totals before pagination
            $totalRebateAmount = (clone $query)->sum('rebate');
            $totalAffiliateRebate = (clone $query)
                ->whereNot('user_id', Auth::id())
                ->sum('rebate');
            $totalPersonalRebate = (clone $query)
                ->where('user_id', Auth::id())
                ->sum('rebate');

            $totalAffiliateLot = (clone $query)
                ->whereNot('user_id', Auth::id())
                ->sum('volume');
            $totalPersonalLot = (clone $query)
                ->where('user_id', Auth::id())
                ->sum('volume');
            $totalTradeLots = (clone $query)->sum('volume');

            if ($data['filters']['type']['value']) {
                $type = $data['filters']['type']['value'];
                if ($type == 'affiliate') {
                    $query->whereNot('user_id', Auth::id());
                } elseif ($type == 'personal') {
                    $query->where('user_id', Auth::id());
                }
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            // Export logic
//            if ($request->has('exportStatus') && $request->exportStatus) {
//                return Excel::download(new TransactionsExport($query), now() . '-'. $data['filters']['type']['value'] . 'report.xlsx');
//            }


            $tradeRebates = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $tradeRebates,
                'totalRebateAmount' => $totalRebateAmount,
                'totalAffiliateRebate' => $totalAffiliateRebate,
                'totalPersonalRebate' => $totalPersonalRebate,
                'totalAffiliateLot' => $totalAffiliateLot,
                'totalPersonalLot' => $totalPersonalLot,
                'totalTradeLots' => $totalTradeLots,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
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
        return Inertia::render('Report/PerformanceIncentive/PerformanceIncentive');
    }

    public function getPerformanceIncentive(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $user = Auth::user();
            $type = $data['filters']['type']['value'];
            $meta_logins = TradingAccount::where('user_id', $user->id)
                ->get()
                ->pluck('meta_login')
                ->toArray();

            $query = PerformanceIncentive::with([
                'user',
                'subscription',
                'subscription.user',
                'pamm_subscription',
                'pamm_subscription.user',
            ])
                ->where('user_id', $user->id);

            if ($type !== 'all') {
                if ($type == 'affiliate') {
                    $query->whereNull('meta_login');
                } elseif ($type == 'personal') {
                    $query->whereIn('meta_login', $meta_logins);
                }
            }

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($outer) use ($keyword) {
                    $outer->whereHas('user', function ($q) use ($keyword) {
                        $q->where('username', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    })
                        ->orWhereHas('subscription.user', function ($q) use ($keyword) {
                            $q->where('username', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        })
                        ->orWhere('meta_login', 'like', "%{$keyword}%")
                        ->orWhereHas('subscription', function ($q) use ($keyword) {
                            $q->where('meta_login', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('pamm_subscription', function ($q) use ($keyword) {
                            $q->where('meta_login', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('pamm_subscription.user', function ($q) use ($keyword) {
                            $q->where('username', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%");
                        });
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            $totalPerformanceIncentive = (clone $query)->sum('personal_bonus_amt');
            $totalAffiliateAmount = (clone $query)->whereNull('meta_login')->sum('personal_bonus_amt');
            $totalPersonalAmount = (clone $query)->whereIn('meta_login', $meta_logins)->sum('personal_bonus_amt');

            $tradeHistories = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $tradeHistories,
                'totalPerformanceIncentive' => (float) $totalPerformanceIncentive,
                'totalAffiliateAmount' => (float) $totalAffiliateAmount,
                'totalPersonalAmount' => (float) $totalPersonalAmount,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
