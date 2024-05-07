<?php

namespace App\Http\Controllers\Api;

use App\Models\Master;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\TradeHistory;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use Illuminate\Support\Carbon;
use App\Models\CopyTradeHistory;
use App\Services\MetaFiveService;
use App\Http\Controllers\Controller;

class MasterController extends Controller
{
    public function getMaster()
    {
        $master = Master::query()
            ->with('subscribers','tradingAccount','tradingUser')
            ->where('status', 'Active')
            ->limit(6)
            ->get();

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            $metaMaster = $master->map(function ($master) {
                return [
                    'subscriber' => $master->subscribers->count('id'),
                    'meta_user' => $master->tradingUser
                ];
            });
            return response()->json([
                'status' => 'failed',
                'meta_user' =>  $metaMaster,
                // 'subscriber' => $metaMaster->,
            ]);
        }

        $MetaData = $master->map(function ($master) use ($metaService) {
            return [
                'subscriber' => $master->subscribers->count('id'),
                'meta_user' =>  $metaService->getMetaUser($master->meta_login),
            ];
        });



        // $metaAccount = $metaService->getMetaUser($master->meta_login);


        return response()->json([
            'status' => 'success',
            'metaUser' => $MetaData
        ]);
    }

    public function getLiveAccount()
    {
        $master = Master::query()
            ->with('subscribers','tradingAccount','tradingUser')
            ->where('status', 'Active')
            ->limit(6)
            ->get();

        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            $metaMaster = $master->map(function ($master) {
                return [
                    'meta_user' => $master->tradingUser
                ];
            });
            return response()->json([
                'status' => 'failed',
                'meta_user' =>  $metaMaster,
                // 'subscriber' => $metaMaster->,
            ]);
        }

        $MetaData = $master->map(function ($master) use ($metaService) {
            // Retrieve the name of the user from the tradingUser table based on meta_login
            $user = TradingUser::where('meta_login', $master->meta_login)->first();
            $name = $user ? $user->name : null;
    
            // Filter trade histories with 'closed' status
            $tradeHistories = $master->tradeHistories->where('trade_status', 'Closed');

            $totalVolume = $tradeHistories->sum('volume');
            $totalTrade = $tradeHistories->count();
            $averageProfit = round($tradeHistories->where('trade_profit', '>', 0)->avg('trade_profit'), 2);
            $averageLoss = round($tradeHistories->where('trade_profit', '<', 0)->avg('trade_profit'), 2);
            $totalProfit = $tradeHistories->where('trade_profit', '>', 0)->sum('trade_profit');
            $totalLoss = $tradeHistories->where('trade_profit', '<', 0)->sum('trade_profit');

            $startDate = '2020-01-01'; // The date for get result
            $currentDate = Carbon::now()->format('Y-m-d H:i:s');

            $dealHistories = $metaService->dealHistory($master->meta_login, $startDate, $currentDate);
            
            // Initialize $earliestDate as empty
            $earliestDate = '';

            // Get the earliest date from the tradeHistory
            $earliestDate = $tradeHistories->min('time_close');
            $earliestDate = date('Y-m-d', strtotime($earliestDate));
            
            // Ensure $dealHistories is a collection
            if (is_array($dealHistories)) {
                $dealHistories = collect($dealHistories);
            }
            
            // Check if $dealHistories is not null before filtering
            if ($dealHistories !== null) {
                // Filter $dealHistories for deposits (action is 2 and profit is positive)
                $totalDeposit = $dealHistories->filter(function($deal) {
                    return $deal['action'] === 2 && $deal['profit'] > 0;
                })->sum('profit');
            
                // Filter $dealHistories for withdrawals (action is 2 and profit is negative)
                $totalWithdrawal = $dealHistories->filter(function($deal) {
                    return $deal['action'] === 2 && $deal['profit'] < 0;
                })->sum('profit');
            } else {
                // Handle the case where $dealHistories is null
                $totalDeposit = 0;
                $totalWithdrawal = 0;
            }
            
            $totalGrowth = $tradeHistories->sum('trade_profit_pct');

            // Assuming the date information is stored in the 'time_close' field
            $currentMonthStartDate = date('Y-m-01');
            $currentMonthEndDate = date('Y-m-t');

            $currentMonthGrowth = $tradeHistories->whereBetween('time_close', [$currentMonthStartDate, $currentMonthEndDate])->sum('trade_profit_pct');

            $maxDailyGrowth = $tradeHistories->max('trade_profit_pct');
                                    
            // Calculate total trades in the last 30 days
            $totalTradesLast30Days = $tradeHistories->where('time_close', '>=', Carbon::today()->subDays(30))->count();

            $latestTrade = $tradeHistories->isNotEmpty() ? strtotime($tradeHistories->max('time_close')) : null;
            $longsWon = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'BUY')->where('trade_profit', '>', 0)->count() : 'N/A';
            $shortsWon = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'SELL')->where('trade_profit', '>', 0)->count() : 'N/A';
            $longsWonPercentage = $totalTrade != 0 ? round($tradeHistories->where('trade_type', 'BUY')->where('trade_profit', '>', 0)->count() / $tradeHistories->where('trade_type', 'BUY')->count() * 100, 2) : 'N/A';
            $shortsWonPercentage = $totalTrade != 0 ? round($tradeHistories->where('trade_type', 'SELL')->where('trade_profit', '>', 0)->count() / $tradeHistories->where('trade_type', 'SELL')->count() * 100, 2) : 'N/A';
                    
            // Get the best and worst trade
            $bestTrade = $tradeHistories->max('trade_profit') ?? 'N/A';
            $worstTrade = $tradeHistories->min('trade_profit') ?? 'N/A';
                    
            // Get the best and worst trade in terms of pips
            $totalLongsTrade = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'BUY')->count() : 'N/A';
            $totalShortsTrade = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'SELL')->count() : 'N/A';

            // Calculate profitability
            $profitability = $averageProfit != 0 && ($averageLoss != 0 && $averageLoss != 1 && $averageLoss != -1) ? ($averageProfit / abs($averageLoss)) * 100 : $averageProfit;

            // Calculate profit factor
            $totalProfits = $tradeHistories->where('trade_profit', '>', 0)->sum('trade_profit');
            $totalLosses = abs($tradeHistories->where('trade_profit', '<', 0)->sum('trade_profit'));
            $profitFactor = ($totalTrade != 0 && $totalLosses != 0) ? $totalProfits / $totalLosses : $totalProfits;

            // Calculate the mean of trade profits
            $mean = $totalTrade != 0 ? $tradeHistories->avg('trade_profit') : null;

            // Calculate the sum of squared differences from the mean
            $sumSquaredDifferences = $tradeHistories->sum(function ($trade) use ($mean) {
                return pow($trade->trade_profit - $mean, 2);
            });

            // Calculate the standard deviation
            $standardDeviation = $totalTrade != 0 ? sqrt($sumSquaredDifferences / $totalTrade) : null;

            $metaAccount = $metaService->getMetaAccount($master->meta_login);
            $metaAccount['name'] = $name;
            $metaAccount['totalProfit'] = $totalProfit;
            $metaAccount['deposits'] = round($totalDeposit, 2);
            $metaAccount['withdrawals'] = round(abs($totalWithdrawal), 2);
            $metaAccount['totalGrowth'] = round($totalGrowth, 2);
            $metaAccount['currentMonthGrowth'] = round($currentMonthGrowth, 2);
            $metaAccount['maxDailyGrowth'] = round($maxDailyGrowth, 2);
            $metaAccount['startDate'] =$earliestDate;
            $metaAccount['totalLot'] = round($totalVolume, 2);
            $metaAccount['totalTrade'] = $totalTrade;
            $metaAccount['profitability'] = round($profitability, 2);
            $metaAccount['averageProfit'] = $averageProfit;
            $metaAccount['averageLoss'] = $averageLoss;
            $metaAccount['totalTradesLast30Days'] = $totalTradesLast30Days;
            $metaAccount['latestTrade'] = date("m/d/Y H:i", $latestTrade);
            $metaAccount['longsWon'] = $longsWon;
            $metaAccount['shortsWon'] = $shortsWon;
            $metaAccount['longsWonPercentage'] = $longsWonPercentage;
            $metaAccount['shortsWonPercentage'] = $shortsWonPercentage;
            $metaAccount['bestTrade'] = $bestTrade;
            $metaAccount['worstTrade'] = $worstTrade;
            $metaAccount['totalLongsTrade'] = $totalLongsTrade;
            $metaAccount['totalShortsTrade'] = $totalShortsTrade;
            $metaAccount['profitFactor'] = round($profitFactor, 2);
            $metaAccount['standardDeviation'] = round($standardDeviation, 2);

            return $metaAccount;
        });
    
        // $metaAccount = $metaService->getMetaUser($master->meta_login);

        return response()->json([
            'status' => 'success',
            'metaUser' => $MetaData
        ]);
    }

    public function getMasterGrowth(Request $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
    
        if ($connection != 0) {
            return response()->json([
                'status' => 'failed',
                'title' =>  trans('public.server_under_maintenance'),
                'warning' => trans('public.try_again_later'),
            ]);
        }
    
        // Get the earliest and latest dates for the meta_login
        $earliestDate = TradeHistory::where('meta_login', $request->meta_login)
            ->where('trade_status', 'Closed')
            ->orderBy('time_close')
            ->value('time_close');

        // Convert the earliest date to a Carbon instance
        $earliestDate = Carbon::parse($earliestDate);

        // Get the current date
        $currentDate = now();

        // Calculate the interval size based on the actual date range
        $intervalDays = $earliestDate->diffInDays($currentDate) / 9; // 9 intervals between 10 points

        // Initialize the interval dates array with the start and end dates
        $intervalDates = [];

        // Add the start date
        $intervalDates[] = $earliestDate->format('Y-m-d');

        // Calculate and add the remaining 8 dates within the intervals
        $currentDatePointer = clone $earliestDate;
        for ($i = 0; $i < 8; $i++) {
            // Increment the current date by the interval size (in days)
            $currentDatePointer->addDays($intervalDays);
            // Add the current date to the interval dates array
            $intervalDates[] = $currentDatePointer->format('Y-m-d');
        }

        // Add the end date
        $intervalDates[] = $currentDate->format('Y-m-d');

        $tradeProfitPct = [];

        // Iterate over the interval dates to calculate cumulative trade profit percentages
        foreach ($intervalDates as $date) {
            // Get the sum of trade profit percentages up to the current date
            $profitUntilDate = TradeHistory::where('meta_login', $request->meta_login)
                ->where('trade_status', 'Closed')
                ->whereDate('time_close', '<=', $date)
                ->sum('trade_profit_pct');

            // Add the sum to the array
            $tradeProfitPct[] = $profitUntilDate;
        }
                            
        // Return the response with the interval dates and cumulative trade profit percentages
        return response()->json([
            'status' => 'success',
            'interval_dates' => $intervalDates,
            'trade_profit_pct' => $tradeProfitPct,
        ]);
    }
        
    public function getMasterLatestTrades(Request $request)
    {    
        // Retrieve the latest 5 distinct created_at dates
        $distinctDates = TradeHistory::selectRaw('DATE(created_at) as date')
            ->where('meta_login', $request->meta_login)
            ->where('trade_status', 'Closed')
            ->groupBy('date')
            ->orderByRaw('MAX(created_at) DESC') // Order by the maximum created_at value for each date
            ->limit(5)
            ->pluck('date');
                    
        // Initialize arrays to store results
        $dateArray = [];
        $longTrades = [];
        $shortTrades = [];
        
        // Loop through distinct dates
        foreach ($distinctDates as $date) {
            // Retrieve the count of long (BUY) winning trades for this date
            $longCount = TradeHistory::where('meta_login', $request->meta_login)
                ->where('trade_type', 'BUY')
                ->where('trade_status', 'Closed')
                ->whereDate('created_at', $date)
                ->count();
    
            // Retrieve the count of short (SELL) winning trades for this date
            $shortCount = TradeHistory::where('meta_login', $request->meta_login)
                ->where('trade_type', 'SELL')
                ->where('trade_status', 'Closed')
                ->whereDate('created_at', $date)
                ->count();
    
            // Store the counts for this date
            $dateArray[] = $date;
            $longTrades[] = $longCount;
            $shortTrades[] = $shortCount;
        }
    
            // Reverse the arrays
            $dateArray = array_reverse($dateArray);
            $longTrades = array_reverse($longTrades);
            $shortTrades = array_reverse($shortTrades);

        return response()->json([
            'dates' => $dateArray,
            'longTrades' => $longTrades,
            'shortTrades' => $shortTrades,
        ]);
    }
    
    public function getMasterCurrency(Request $request)
    {
        // Retrieve all distinct symbols
        $distinctSymbols = TradeHistory::select('symbol')
            ->where('meta_login', $request->meta_login)
            ->where('trade_status', 'Closed')
            ->distinct()
            ->pluck('symbol');
    
        // Get the total count of all symbols
        $totalCount = TradeHistory::where('meta_login', $request->meta_login)
            ->where('trade_status', 'Closed')
            ->count();
    
        // Initialize an array to store the formatted data
        $currency = [];
    
        // Loop through distinct symbols
        foreach ($distinctSymbols as $symbol) {
            // Retrieve the count of trades for this symbol
            $symbolCount = TradeHistory::where('meta_login', $request->meta_login)
                ->where('symbol', $symbol)
                ->where('trade_status', 'Closed')
                ->count();
    
            // Calculate the percentage of trades for this symbol
            $percentage = ($symbolCount / $totalCount) * 100;
    
            // Format the data for this symbol
            $currency[] = [
                'label' => $symbol,
                'percentage' => round($percentage, 2) // Round to 2 decimal places
            ];
        }
    
        return response()->json($currency);
    }
        
    public function getMasterOpenTrade(Request $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();
        $userTrade = CopyTradeHistory::where('user_type', 'master')->where('meta_login', $request->meta_login)->where('status', 'open')->whereDate('time_open', '>','2024-04-15')->latest()->get();

        if ($connection != 0) {
            return response()->json([
                'status' => 'failed',
                'title' =>  trans('public.server_under_maintenance'),
                'warning' => trans('public.try_again_later'),
            ]);
        }
        
        // Format the timeCreated attribute in $userTrade data
        foreach ($userTrade as &$trade) {
            $trade['timeCreated'] = date('d/m H:i', $trade['timeCreated']);
        }

        return response()->json([
            'status' => 'success',
            'openTrade' => $userTrade
        ]);
        
    }
}
