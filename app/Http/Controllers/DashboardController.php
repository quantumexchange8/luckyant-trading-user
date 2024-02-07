<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\TradingAccount;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $announcement = Announcement::where('type', 'login')->latest()->first();

        $announcement->image = $announcement->getFirstMediaUrl('announcement');

        return Inertia::render('Dashboard', [
            'announcement' => $announcement,
            'firstTimeLogin' => \Session::get('first_time_logged_in'),
            'cashWallet' => Wallet::where('user_id', \Auth::id())->where('type', 'cash_wallet')->first()
        ]);
    }

    public function getBalanceChart()
    {
        $user = \Auth::user();

        // Get Cash Wallet balance
        $wallets = Wallet::query()
            ->where('user_id', $user->id)
            ->select('id', 'name', 'type', 'balance')
            ->get();

        // Get trading account balances
        $tradingAccounts = TradingAccount::where('user_id', $user->id)->get();

        $chartData = [
            'labels' => $wallets->pluck('name'), // Initial label for Cash Wallet
            'datasets' => [],
        ];

        foreach ($wallets as $wallet) {
            $balances[] = $wallet->balance;
        }
        $backgroundColors = ['#598fd8']; // Background color for Cash Wallet

        // Hardcoded 10 background colors for trading accounts
        $tradingAccountColors = ["#607d8b", "#42515c", "#3c474e", "#4575cb", "#0060ff", "#3b62ba", "#79ade1", "#2f4579", "#00ffff"];

        $i = 0; // Index to iterate through the tradingAccountColors array

        foreach ($tradingAccounts as $account) {
            $chartData['labels'][] = 'Login: ' . $account->meta_login;
            $balances[] = number_format($account->balance, 2, '.', '');

            // Assigning a different background color for each trading account
            $backgroundColors[] = $tradingAccountColors[$i];

            // Increment the index or loop back to the beginning if we reach the end of the tradingAccountColors array
            $i = ($i + 1) % count($tradingAccountColors);
        }

        $dataset = [
            'data' => $balances,
            'backgroundColor' => $backgroundColors,
            'offset' => 5,
            'borderColor' => 'transparent'
        ];

        $chartData['datasets'][] = $dataset;

        return response()->json($chartData);
    }

    public function update_session()
    {
        \Session::put('first_time_logged_in', 0);

        return back();
    }
}
