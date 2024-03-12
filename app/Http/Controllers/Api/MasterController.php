<?php

namespace App\Http\Controllers\Api;

use App\Models\CopyTradeHistory;
use App\Models\Master;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
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

    public function getMasterLiveTrades(Request $request)
    {
        $startDate = today()->subWeek();
        $endDate = today();

        $tradeHistories = CopyTradeHistory::where('user_type', 'master')
            ->where('meta_login', $request->meta_login)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json($tradeHistories);
    }
}
