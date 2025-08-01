<?php

namespace App\Http\Controllers\Api;

use App\Models\Master;
use App\Models\TradingUser;
use App\Models\Transaction;
use App\Models\TradeHistory;
use App\Models\User;
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
        $orderedMetaLogins = [
            457285,
            457286,
            457312,
            459189,
            460257,
            458213,
        ];

        $master = Master::query()
            ->with(['subscribers', 'tradingAccount', 'tradingUser'])
            ->where('status', 'Active')
            ->whereIn('meta_login', $orderedMetaLogins)
            ->orderByRaw('FIELD(meta_login, ' . implode(',', $orderedMetaLogins) . ')')
            ->get()
            ->unique('meta_login')
            ->values();
        
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
        $orderedMetaLogins = [
            457285,
            457286,
            457312,
            459189,
            460257,
            458213,
        ];
        
        $master = Master::query()
            ->with(['subscribers', 'tradingAccount', 'tradingUser'])
            ->where('status', 'Active')
            ->whereIn('meta_login', $orderedMetaLogins)
            ->orderByRaw('FIELD(meta_login, ' . implode(',', $orderedMetaLogins) . ')')
            ->get()
            ->unique('meta_login')
            ->values();
        
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

            $startDate = '2020-01-01'; // The date for get result
            $currentDate = Carbon::now()->format('Y-m-d H:i:s');

            $dealHistories = $metaService->dealHistory($master->meta_login, $startDate, $currentDate);

            // foreach ($dealHistories as &$deal) {
            //     // Convert "time" to date
            //     $deal['time'] = date('Y-m-d H:i:s', $deal['time']);
            // }

            // // Filter deal histories with profit less than 0
            // $filteredDealHistories = array_filter($dealHistories, function($deal) {
            //     return $deal['action'] === 2 && $deal['profit'] < 0;
            // });

            // // Display filtered deal histories
            // dd($filteredDealHistories);

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

            // Calculate total trades in the last 30 days
            $totalTradesLast30Days = $tradeHistories->where('time_close', '>=', Carbon::today()->subDays(30))->count();

            $latestTrade = $tradeHistories->isNotEmpty() ? strtotime($tradeHistories->max('time_close')) : null;
            $longsWon = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'BUY')->where('trade_profit', '>', 0)->count() : 'N/A';
            $shortsWon = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'SELL')->where('trade_profit', '>', 0)->count() : 'N/A';
            $totalWonTrade = $totalTrade != 0 ? $tradeHistories->where('trade_profit', '>', 0)->count() : 'N/A';
            $totalLossTrade = $totalTrade != 0 ? $tradeHistories->where('trade_profit', '<', 0)->count() : 'N/A';
            $longsWonPercentage = $totalTrade != 0 ? round($tradeHistories->where('trade_type', 'BUY')->where('trade_profit', '>', 0)->count() / $tradeHistories->where('trade_type', 'BUY')->count() * 100, 2) : 'N/A';
            $shortsWonPercentage = $totalTrade != 0 ? round($tradeHistories->where('trade_type', 'SELL')->where('trade_profit', '>', 0)->count() / $tradeHistories->where('trade_type', 'SELL')->count() * 100, 2) : 'N/A';

            // Get the best and worst trade
            $bestTrade = $tradeHistories->max('trade_profit') ?? 'N/A';
            $worstTrade = $tradeHistories->min('trade_profit') ?? 'N/A';

            // Get the best and worst trade in terms of pips
            $totalLongsTrade = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'BUY')->count() : 'N/A';
            $totalShortsTrade = $totalTrade != 0 ? $tradeHistories->where('trade_type', 'SELL')->count() : 'N/A';

            // Calculate profit factor
            $totalProfits = $tradeHistories->where('trade_profit', '>', 0)->sum('trade_profit');
            $totalLosses = abs($tradeHistories->where('trade_profit', '<', 0)->sum('trade_profit'));
            $profitFactor = ($totalProfits != 0 && $totalLosses != 0) ? $totalProfits / $totalLosses : 0;

            // Calculate profitability
            $profitability = $totalTrade != 0 && $totalWonTrade != 0 ? (($totalWonTrade) / $totalTrade) * 100 : 0;

            // Calculate the mean of trade profits
            $mean = $totalTrade != 0 ? $tradeHistories->avg('trade_profit') : null;

            // Calculate the sum of squared differences from the mean
            $sumSquaredDifferences = $tradeHistories->sum(function ($trade) use ($mean) {
                return pow($trade->trade_profit - $mean, 2);
            });

            // Calculate the standard deviation
            $standardDeviation = $totalTrade != 0 ? sqrt($sumSquaredDifferences / $totalTrade) : null;

            // Calculate the total profit
            $totalProfit = $tradeHistories->sum('trade_profit');

            // Check if $dealHistories is not null before filtering
            if ($dealHistories !== null) {
                // Filter $dealHistories for withdrawals (action is 2 and profit is negative)
                $Withdrawal = $dealHistories->filter(function($deal) {
                    return $deal['action'] === 2 && $deal['profit'] < 0;
                });

                // Find the initial capital (first deposit)
                $initialCapital = $dealHistories->first(function ($deal) {
                    return $deal['action'] === 2 && $deal['profit'] > 0;
                });
                $initialCapital = $initialCapital ? $initialCapital['profit'] : 0;
            } else {
                // Handle the case where $dealHistories is null
                $Withdrawal = '';
                $initialCapital = 0;
            }

            if($Withdrawal != null) {
            // Iterate through each item in the $Withdrawal array
                $Withdrawal->transform(function($item) {
                    // Convert the UNIX timestamp to a date format
                    $item['time'] = Carbon::createFromTimestamp($item['time'])->toDateTimeString();
                    return $item;
                });
            }

            $tradeHistories = TradeHistory::where('meta_login', $master->meta_login)
                    ->where('trade_status', 'Closed')
                    ->get();

            // Merge the withdrawal records into $tradeHistories based on time_close
            $tradeHistories = $tradeHistories->concat($Withdrawal->map(function($withdrawal) {
                $withdrawalRecord = new \stdClass();
                $withdrawalRecord->meta_login = $withdrawal['login'];
                $withdrawalRecord->symbol = ''; // You may need to set the symbol here
                $withdrawalRecord->trade_type = 'Withdrawal';
                $withdrawalRecord->time_close = $withdrawal['time']; // Assuming time_close is used for withdrawal time
                $withdrawalRecord->trade_profit = $withdrawal['profit'];
                return $withdrawalRecord;
            }));

            // Sort the combined collection by time_close in descending order
            $tradeHistories = $tradeHistories->sortBy(function ($trade) {
                return strtotime($trade->time_close);
            })->values();

            $tradeHistories->transform(function ($trade) {
                $trade->time_close = date('Y-m-d', strtotime($trade->time_close));
                return $trade;
            });

            $initialInvestment = $initialCapital;

            // Calculate trade profit percentage for each trade
            foreach ($tradeHistories as $trade) {
                $trade->initialInvestment = round($initialInvestment, 2);
                if($trade->trade_type != 'Withdrawal') {
                    // Calculate trade profit percentage
                    $tradeProfitPercentage = ($trade->trade_profit / $initialInvestment) * 100;

                    // Add initial investment and tradeProfitPercentage key to the trade record
                    $trade->tradeProfitPercentage = round($tradeProfitPercentage, 2);

                }

                // Update initial investment for next iteration
                $initialInvestment += $trade->trade_profit;
            }

            // Group trade histories by date and calculate total growth for each day
            $groupedTradeHistories = $tradeHistories->groupBy('time_close')->map(function($trades) {
                return $trades->sum('tradeProfitPercentage');
            });

            // Calculate total growth, current month growth, and max daily growth
            $totalGrowth = $groupedTradeHistories->sum();
            $currentMonthStartDate = date('Y-m-01');
            $currentMonthEndDate = date('Y-m-t');
            $currentMonthGrowth = $groupedTradeHistories->filter(function($value, $key) use ($currentMonthStartDate, $currentMonthEndDate) {
                return ($key >= $currentMonthStartDate && $key <= $currentMonthEndDate);
            })->sum();
            $maxDailyGrowth = $groupedTradeHistories->max();

            // Round the values to two decimal places
            $totalGrowth = round($totalGrowth, 2);
            $currentMonthGrowth = round($currentMonthGrowth, 2);
            $maxDailyGrowth = round($maxDailyGrowth, 2);

            $metaAccount = $metaService->getMetaAccount($master->meta_login);
            $metaAccount['name'] = $name;
            $metaAccount['totalProfit'] = round($totalProfit, 2);
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

        $startDate = '2020-01-01'; // The date for get result
        $currentDate = Carbon::now()->format('Y-m-d H:i:s');

        $dealHistories = $metaService->dealHistory($request->meta_login, $startDate, $currentDate);

        // Ensure $dealHistories is a collection
        if (is_array($dealHistories)) {
            $dealHistories = collect($dealHistories);
        }

        // Check if $dealHistories is not null before filtering
        if ($dealHistories !== null) {
            // Filter $dealHistories for withdrawals (action is 2 and profit is negative)
            $Withdrawal = $dealHistories->filter(function($deal) {
                return $deal['action'] === 2 && $deal['profit'] < 0;
            });

            // Find the initial capital (first deposit)
            $initialCapital = $dealHistories->first(function ($deal) {
                return $deal['action'] === 2 && $deal['profit'] > 0;
            });
            $initialCapital = $initialCapital ? $initialCapital['profit'] : 0;
        } else {
            // Handle the case where $dealHistories is null
            $Withdrawal = '';
            $initialCapital = 0;
        }

        if($Withdrawal != null) {
        // Iterate through each item in the $Withdrawal array
            $Withdrawal->transform(function($item) {
                // Convert the UNIX timestamp to a date format
                $item['time'] = Carbon::createFromTimestamp($item['time'])->toDateTimeString();
                return $item;
            });
        }

        $tradeHistories = TradeHistory::where('meta_login', $request->meta_login)
                ->where('trade_status', 'Closed')
                ->get();

        // Merge the withdrawal records into $tradeHistories based on time_close
        $tradeHistories = $tradeHistories->concat($Withdrawal->map(function($withdrawal) {
            $withdrawalRecord = new \stdClass();
            $withdrawalRecord->meta_login = $withdrawal['login'];
            $withdrawalRecord->symbol = ''; // You may need to set the symbol here
            $withdrawalRecord->trade_type = 'Withdrawal';
            $withdrawalRecord->time_close = $withdrawal['time']; // Assuming time_close is used for withdrawal time
            $withdrawalRecord->trade_profit = $withdrawal['profit'];
            return $withdrawalRecord;
        }));

        // Sort the combined collection by time_close in descending order
        $tradeHistories = $tradeHistories->sortBy(function ($trade) {
            return strtotime($trade->time_close);
        })->values();

        $initialInvestment = $initialCapital;

        // Calculate trade profit percentage for each trade
        foreach ($tradeHistories as $trade) {
            $trade->initialInvestment = round($initialInvestment, 2);
            if($trade->trade_type != 'Withdrawal') {
                // Calculate trade profit percentage
                $tradeProfitPercentage = ($trade->trade_profit / $initialInvestment) * 100;

                // Add initial investment and tradeProfitPercentage key to the trade record
                $trade->tradeProfitPercentage = round($tradeProfitPercentage, 2);

            }

            // Update initial investment for next iteration
            $initialInvestment += $trade->trade_profit;

        }

        $tradeProfitPct = [];

        // Iterate over the interval dates to calculate cumulative trade profit percentages
        foreach ($intervalDates as $date) {
            // Get the sum of trade profit percentages up to the current date
            $profitUntilDate = $tradeHistories->where('meta_login', $request->meta_login)
                ->where('trade_status', 'Closed')
                ->where('time_close', '<=', $date)
                ->sum('tradeProfitPercentage');

            // Add the sum to the array
            $tradeProfitPct[] = round($profitUntilDate, 2);
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
        $userTrade = $metaService->userTrade($request->meta_login);
        // $userTrade = CopyTradeHistory::where('user_type', 'master')->where('meta_login', $request->meta_login)->where('status', 'open')->whereDate('time_open', '>','2024-04-15')->latest()->get();

        if ($connection != 0) {
            return response()->json([
                'status' => 'failed',
                'title' =>  trans('public.server_under_maintenance'),
                'warning' => trans('public.try_again_later'),
            ]);
        }

        // Ensure $userTrade is an array
        if (!is_array($userTrade)) {
            $userTrade = []; // Convert to empty array if null or not an array
        }

        if (is_array($userTrade)) {
            // Format the timeCreated attribute, set the action attribute, and divide the volume in $userTrade data
            foreach ($userTrade as &$trade) {
                $trade['timeCreated'] = date('d/m/Y H:i:s', $trade['timeCreated']);
                $trade['action'] = $trade['action'] == 0 ? 'BUY' : 'SELL';
                $trade['volume'] = $trade['volume'] / 10000;
            }
            unset($trade); // Break the reference with the last element
        } else {
            // Handle the case when $userTrade is not an array
            // This could involve setting $userTrade to an empty array, logging an error, etc.
            $userTrade = []; // or handle as needed
        }

        return response()->json([
            'status' => 'success',
            'openTrade' => $userTrade
        ]);
    }

    // Mall Api
    public function sync_trading_master(Request $request)
    {
        $data = $request->all();

        if (!empty($data)) {
            $user = User::firstWhere([
                'username' => $data['username'],
            ]);

            $master_data = $data['master'];

            $master = Master::create([
                'user_id' => $user->id,
                'meta_login' => $master_data['meta_login'],
                'category' => 'pamm',
                'type' => $master_data['type'] ?? 'CopyTrade',
                'min_join_equity' => $master_data['min_investment'],
                'sharing_profit' => $master_data['sharing_profit'],
                'market_profit' => $master_data['market_profit'],
                'company_profit' => $master_data['sa_profit'],
                'subscription_fee' => $master_data['subscription_fee'] ?? 0,
                'signal_status' => 1,
                'estimated_monthly_returns' => $master_data['estimated_monthly_returns'],
                'estimated_lot_size' => $master_data['estimated_lot_size'],
                'join_period' => $master_data['join_period'],
                'roi_period' => $master_data['settlement_period'],
                'total_subscribers' => 0,
                'total_fund' => 0,
                'is_public' => 0,
                'project_based' => 'CH',
                'can_top_up' => 0,
                'can_revoke' => 0,
                'status' => 'Inactive',
            ]);

            $master->delete();

            return response()->json([
                'success' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No data found'
        ]);
    }
}
