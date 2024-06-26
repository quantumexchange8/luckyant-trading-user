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
            'master_login' => $data['master_login'],
            'amount' => $data['amount'],
        ];
    }
}
