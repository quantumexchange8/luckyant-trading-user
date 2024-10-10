<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\Subscription;
use App\Models\TradePammInvestorAllocate;
use App\Models\TradingAccount;
use App\Models\TradingUser;
use App\Services\dealAction;
use App\Services\MetaFiveService;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PammController extends Controller
{
    public function get_investment_strategy_list()
    {
        return response()->json([
           'strategy' => Master::where('type', 'PAMM')->get()
        ]);
    }

    public function subscribe_to_investment_strategy()
    {
        $subscriptions = Subscription::where('type', 'PAMM')
            ->where('status', 'Active')
            ->get();

        return response()->json([
            'subscriptions' => $subscriptions
        ]);
    }

    public function join_investment_strategy(Request $request)
    {
        $data = $request->all();

        $result = [
            'follower_id' => $data['follower_id'],
            'master_id' => $data['master_id'],
            'amount' => $data['amount'],
        ];

        $trading_user = TradingUser::withTrashed()
            ->where('meta_login', $data['follower_id'])
            ->first();

        $masterAccount = Master::find($result['master_id']);

        $pamm_subscription = PammSubscription::create([
            'user_id' => $trading_user ?->user_id,
            'meta_login' => $result['follower_id'],
            'master_id' => $masterAccount->id,
            'master_meta_login' => $masterAccount->meta_login,
            'subscription_amount' => $result['amount'],
            'type' => $masterAccount->type,
            'subscription_number' => RunningNumberService::getID('subscription'),
            'subscription_period' => $masterAccount->join_period,
            'settlement_period' => $masterAccount->roi_period,
            'settlement_date' => now()->addDays($masterAccount->roi_period)->endOfDay(),
            'expired_date' => now()->addDays($masterAccount->join_period)->endOfDay(),
            'approval_date' => now(),
            'max_out_amount' => $masterAccount->max_out_amount,
            'status' => 'Active',
            'remarks' => 'China PAMM',
            'extra_conditions' => $trading_user ? 'NOLOT' : 'NOLOT_NOPAMM',
        ]);

        // fund to master
        $description = 'deposit #' . $pamm_subscription->meta_login;
        $master_deal = [];

        try {
            $master_deal = (new MetaFiveService())->createDeal($pamm_subscription->master_meta_login, $pamm_subscription->subscription_amount, $description, dealAction::DEPOSIT);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

//        $response = Http::post('https://api.luckyantmallvn.com/serverapi/pamm/subscription/join', $pamm_subscription);
//        \Log::debug($response);

        $pamm_subscription->delete();

        $masterAccount->total_fund += $result['amount'];
        $masterAccount->save();
//        $master_response = \Http::post('https://api.luckyantmallvn.com/serverapi/pamm/strategy', $masterAccount);
//        \Log::debug($master_response);

        return response()->json([
            'status' => 'success',
            'message' => 'Joined pamm master',
            'strategy_number' => $pamm_subscription->subscription_number,
        ]);
    }

    public function revoke_investment_strategy(Request $request)
    {
        $data = $request->all();

        $result = [
            'follower_id' => $data['follower_id'],
            'master_id' => $data['master_id'],
            'amount' => $data['amount'],
        ];

        $pamm_subscriptions = PammSubscription::withTrashed()
            ->where('meta_login', $result['follower_id'])
            ->where('master_id', $result['master_id']);

        if ($pamm_subscriptions->get()->isEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Strategy not found'
            ]);
        }

        $total_amount = $pamm_subscriptions->sum('subscription_amount');

        if ($total_amount != $result['amount']) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Amount mismatch'
            ]);
        }

        foreach ($pamm_subscriptions->get() as $pamm_subscription) {
            // Check if the strategy is already revoked
            if ($pamm_subscription->status == 'Revoked') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Strategy already revoked'
                ]);
            }

            // Update subscription status and termination date
            $pamm_subscription->update([
                'termination_date' => now(),
                'status' => 'Revoked',
            ]);
        }

        // Find the master account
        $masterAccount = Master::find($result['master_id']);

        if (!$masterAccount) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Master account not found'
            ]);
        }

        // Perform a single withdrawal for the total amount
        $description = 'withdraw #' . $result['follower_id'];
        $master_deal = [];

        try {
            $master_deal = (new MetaFiveService())->createDeal(
                $pamm_subscriptions->first()->master_meta_login,
                $total_amount,
                $description,
                dealAction::WITHDRAW
            );
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: ' . $e->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to process withdrawal'
            ]);
        }

        // Update master account fund
        $masterAccount->total_fund -= $result['amount'];
        $masterAccount->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Revoked PAMM strategy',
        ]);
    }

    public function getStrategySummary(Request $request)
    {
        $data = $request->all();

        if (!isset($data['master_id'])) {
            return response()->json([
               'status' => 'failed',
               'message' => 'Missing master_id'
            ]);
        }

        $result = [
            'master_id' => $data['master_id'],
            'from' => $data['from'] ?? today()->toDateString(),
            'to' => $data['to'] ?? today()->toDateString(),
        ];

        $pammSummaries = TradePammInvestorAllocate::where('master_id', $result['master_id'])
            ->when($request->filled('from') || $request->filled('to'), function ($query) use ($result) {
                $start_date = Carbon::createFromFormat('Y-m-d', $result['from'])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $result['to'])->endOfDay();
                $query->whereBetween('time_close', [$start_date, $end_date]);
            })
            ->get()
            ->groupBy(function ($data) {
                // Group by date, master_id, and master_meta_login
                return Carbon::parse($data->time_close)->format('Y-m-d') . '|' . $data->master_id . '|' . $data->master_meta_login;
            })
            ->map(function ($groupedData, $key) {
                list($date, $masterId, $masterMetaLogin) = explode('|', $key);

                // Aggregate total lot and profit_and_loss
                $totalLot = $groupedData->sum('volume');
                $totalProfitLoss = $groupedData->sum('trade_profit');

                // Aggregate followers' data
                $followers = $groupedData->groupBy('meta_login')->map(function ($groupedFollowers) {
                    return [
                        'meta_login' => $groupedFollowers->first()->meta_login,
                        'lot' => $groupedFollowers->sum('volume'),
                        'profit_and_loss' => $groupedFollowers->sum('trade_profit'),
                    ];
                })->values();

                return [
                    'date' => $date,
                    'master_id' => $masterId,
                    'master_meta_login' => $masterMetaLogin,
                    'master_total_lot' => $totalLot,
                    'master_total_profit_and_loss' => $totalProfitLoss,
                    'followers' => $followers,
                ];
            })
            ->values();

        return response()->json([
           'status' => 'success',
            'data' => $pammSummaries
        ]);
    }

    public function getStrategyDetails(Request $request)
    {
        $data = $request->all();

        if (!isset($data['master_id'])) {
            return response()->json([
               'status' => 'failed',
               'message' => 'Missing master_id'
            ]);
        }

        $result = [
            'master_id' => $data['master_id'],
            'from' => $data['from'] ?? today()->toDateString(),
            'to' => $data['to'] ?? today()->toDateString(),
        ];


        $pammSummaries = TradePammInvestorAllocate::where('master_id', $result['master_id'])
            ->when($request->filled('from') || $request->filled('to'), function ($query) use ($result) {
                $start_date = Carbon::createFromFormat('Y-m-d', $result['from'])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $result['to'])->endOfDay();
                $query->whereBetween('time_close', [$start_date, $end_date]);
            })
            ->get()
            ->groupBy(function ($data) {
                // Group by date, master_id, and master_meta_login
                return Carbon::parse($data->time_close)->format('Y-m-d') . '|' . $data->master_id . '|' . $data->master_meta_login;
            })
            ->map(function ($groupedData, $key) {
                list($date, $masterId, $masterMetaLogin) = explode('|', $key);

                // Aggregate total lot and profit_and_loss
                $totalLot = $groupedData->sum('volume');
                $totalProfitLoss = $groupedData->sum('trade_profit');

                // Aggregate followers' data with details
                $followers = $groupedData->groupBy('meta_login')->map(function ($groupedFollowers) {
                    $details = $groupedFollowers->map(function ($follower) {
                        return [
                            'ticket' => $follower->ticket,
                            'symbol' => $follower->symbol,
                            'trade_type' => $follower->trade_type,
                            'volume' => $follower->volume,
                            'trade_profit' => $follower->trade_profit,
                            'time_close' => $follower->time_close,
                        ];
                    });
                    return [
                        'meta_login' => $groupedFollowers->first()->meta_login,
                        'lot' => $groupedFollowers->sum('volume'),
                        'profit_and_loss' => $groupedFollowers->sum('trade_profit'),
                        'details' => $details,
                    ];
                })->values();

                return [
                    'date' => $date,
                    'master_id' => $masterId,
                    'master_meta_login' => $masterMetaLogin,
                    'master_total_lot' => $totalLot,
                    'master_total_profit_and_loss' => $totalProfitLoss,
                    'followers' => $followers,
                ];
            })
            ->values();

        return response()->json([
           'status' => 'success',
            'data' => $pammSummaries
        ]);
    }

    public function withdrawStrategyProfit(Request $request)
    {
        $data = $request->all();

        if (!isset($data['follower_id'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing follower_id'
            ]);
        }

        if (!isset($data['master_id'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing master_id'
            ]);
        }

        $result = [
            'follower_id' => $data['follower_id'],
            'master_id' => $data['master_id'],
            'amount' => $data['amount'],
        ];

        $masterAccount = Master::find($result['master_id']);

        // deduct from master
        $description = 'withdrawal #' . $result['follower_id'];
        $master_deal = [];

        try {
            $master_deal = (new MetaFiveService())->createDeal($masterAccount->meta_login, $result['amount'], $description, dealAction::WITHDRAW);
        } catch (\Exception $e) {
            \Log::error('Error fetching trading accounts: '. $e->getMessage());
        }

        $masterAccount->total_fund -= $result['amount'];
        $masterAccount->save();

        // deduct trading account
        $tradingAccount = TradingAccount::onlyTrashed()
            ->where('meta_login', $result['follower_id'])
            ->first();

        if ($tradingAccount) {
            try {
                (new MetaFiveService())->createDeal($tradingAccount->meta_login, $result['amount'], 'Withdrawal', dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error withdraw trading account: '. $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Withdraw amount $ ' . $result['amount'] . ' from #' . $result['follower_id'],
        ]);
    }

    public function withdrawBalance(Request $request)
    {
        $data = $request->all();

        if (!isset($data['follower_id'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing follower_id'
            ]);
        }

        $result = [
            'follower_id' => $data['follower_id'],
            'amount' => $data['amount'],
        ];

        $pamm_subscription = PammSubscription::onlyTrashed()
            ->where('meta_login', $result['follower_id'])
            ->where('status', 'Active')
            ->first();

        if ($pamm_subscription) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Follower following PAMM #' . $pamm_subscription->master_meta_login
            ]);
        }

        // deduct trading account
        $tradingAccount = TradingAccount::onlyTrashed()
            ->where('meta_login', $result['follower_id'])
            ->first();

        if ($tradingAccount) {
            try {
                (new MetaFiveService())->createDeal($tradingAccount->meta_login, $result['amount'], 'Withdrawal', dealAction::WITHDRAW);
            } catch (\Exception $e) {
                \Log::error('Error withdraw trading account: '. $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Withdraw amount $ ' . $result['amount'] . ' from #' . $result['follower_id'],
        ]);
    }
}
