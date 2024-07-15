<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\Subscription;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $checkSubscription = PammSubscription::find($result['follower_id']);
        $masterAccount = Master::find($result['master_id']);

        if ($checkSubscription) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Follower id must be unique'
            ]);
        }

        $pamm_subscription = PammSubscription::create([
            'master_id' => $masterAccount->id,
            'master_meta_login' => $masterAccount->meta_login,
            'subscription_amount' => $result['amount'],
            'type' => $masterAccount->type,
            'subscription_number' => RunningNumberService::getID('subscription'),
            'subscription_period' => $masterAccount->join_period,
            'settlement_period' => $masterAccount->roi_period,
            'settlement_date' => now()->addDays($masterAccount->roi_period)->startOfDay(),
            'expired_date' => now()->addDays($masterAccount->join_period)->endOfDay(),
            'max_out_amount' => $masterAccount->max_out_amount,
            'status' => 'Pending',
            'remarks' => 'China PAMM'
        ]);

        $response = Http::post('https://api.luckyantmallvn.com/serverapi/pamm/subscription/join', $pamm_subscription);
        \Log::debug($response);

        $pamm_subscription->delete();

        $masterAccount->total_fund += $result['amount'];
        $masterAccount->save();
        $master_response = \Http::post('https://api.luckyantmallvn.com/serverapi/pamm/strategy', $masterAccount);
        \Log::debug($master_response);

        return response()->json([
            'status' => 'success',
        ]);
    }
}
