<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TradingUser;
use App\Models\Master;
use App\Models\PammSubscription;
use App\Models\PammReportLog;
use App\Models\TradePammInvestorAllocate;
use App\Notifications\PammDailyReportNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Console\Command;

class PammDailyReportCommand extends Command
{
    protected $signature = 'notify:pamm-report';

    protected $description = 'PAMM Daily Profit Report';

    public function handle(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $pammReport = TradePammInvestorAllocate::whereDate('time_close', $yesterday)
            ->get()
            ->groupBy('user_id')
            ->map(function ($userGroups, $userId) {
                $user = User::select('id', 'name', 'email')
                ->where('id', $userId)
                ->first();

                $masterGroups = $userGroups->groupBy('master_id')->map(function ($masterGroup, $masterId) {   
                    $masterData = Master::with(['tradingUser' => function ($query) {
                        $query->select('id', 'name');
                    }])
                    ->select('meta_login', 'trading_account_id')
                    ->where('id', $masterId)
                    ->first();

                    $masterName = $masterData->tradingUser->name;
                    $masterCompany = $masterData->tradingUser->company ? $masterData->tradingUser->company : $masterName;

                    $personals = $masterGroup->groupBy('meta_login')->map(function ($personalGroup) {
                        $subData = PammSubscription::selectRaw('MIN(approval_date) as join_date')
                        ->where('meta_login', $personalGroup->first()->meta_login)
                        ->get();

                        $details = $personalGroup->map(function ($personal) {
                            return [
                                'ticket' => $personal->ticket,
                                'symbol' => $personal->symbol,
                                'trade_type' => $personal->trade_type,
                                'volume' => $personal->volume,
                                'trade_profit' => round($personal->trade_profit, 2),
                                'time_close' => $personal->time_close,
                            ];
                        });
                        return [
                            'meta_login' => $personalGroup->first()->meta_login,
                            'join_date' => Carbon::parse($subData->first()->join_date)->format('Y-m-d'),
                            'subscription_amount' => $personalGroup->first()->subscription_funds,
                            'profit_and_loss' => round($personalGroup->sum('trade_profit'), 2),
                            'details' => $details,
                        ];
                    })->values();

                    return [
                        'master_meta_login' => $masterData->meta_login,
                        'master_name' => $masterName,
                        'master_company' => $masterCompany,
                        'total_master_lot' => round($masterGroup->sum('master_lot'), 2),
                        'total_master_profit_and_loss' => round($masterGroup->sum('master_profit'), 2),
                        'personal' => $personals,
                    ];
                })->values();

                return [
                    'date' => Carbon::yesterday()->toDateString(),
                    'user' => $user,
                    'master_group' => $masterGroups,
                ];
            })
            ->values();

        foreach ($pammReport as $pamm) {
            $user = $pamm['user'];
            Notification::route('mail', $user['email'])
            ->notify(new PammDailyReportNotification($user, $pamm));

            PammReportLog::create([
                'user_id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ]);
        }
    }
}
