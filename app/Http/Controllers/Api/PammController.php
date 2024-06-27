<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master;
use App\Models\Subscription;
use Illuminate\Http\Request;

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

        $checkSubscription = Subscription::find($result['follower_id']);

        if ($checkSubscription) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Follower id must be unique'
            ]);
        }

        $subscription = Subscription::create([
            'meta_login' => $result['follower_id'],
            'master_id' => $result['master_id'],
            'meta_balance' => $result['amount'],
            'type' => 'PAMM',
            'remarks' => 'China Mall PAMM'
        ]);

        $subscription_response = \Http::post('http://103.21.90.87:8080/serverapi/pamm/subscription/join', $subscription);
        \Log::debug($subscription_response);

        $subscription->delete();

        $master = Master::find($result['master_id']);

        $master->total_fund += $result['amount'];
        $master->save();
        $master_response = \Http::post('http://103.21.90.87:8080/serverapi/pamm/strategy', $master);
        \Log::debug($master_response);

        return response()->json([
            'status' => 'success',
        ]);
    }
}
