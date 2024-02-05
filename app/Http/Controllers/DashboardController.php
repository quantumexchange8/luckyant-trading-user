<?php

namespace App\Http\Controllers;

use App\Models\TradingAccount;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {

    }

    public function getBalanceChart()
    {
        $user = \Auth::user();

        // Get Cash Wallet balance
        $cashWalletBalance = $user->cash_wallet;

        // Get trading account balances
        $tradingAccounts = TradingAccount::where('user_id', $user->id)->get();

        $chartData = [
            'labels' => ['Cash Wallet'], // Initial label for Cash Wallet
            'datasets' => [],
        ];

        $balances = [$cashWalletBalance]; // Initial balance for Cash Wallet
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
}
