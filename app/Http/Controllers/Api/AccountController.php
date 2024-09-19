<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradingAccount;
use App\Models\User;
use App\Notifications\AddTradingAccountNotification;
use App\Services\MetaFiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function create_account(Request $request)
    {
        $metaService = new MetaFiveService();
        $connection = $metaService->getConnectionStatus();

        if ($connection != 0) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Connection to MT5 server failed',
            ]);
        }

        $data = $request->all();

        $user = User::withTrashed()
            ->where('email', $data['email'])
            ->first();

        if (empty($user)) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('lucky1234.'),
                'remember_token' => Str::random(10),
                'country' => 45,
                'phone' => '+86' . rand(1000000, 9999999),
                'dob' => '1990-09-12',
                'remark' => 'china_pamm',
                'status' => 'Inactive',
            ]);
        }

        $metaAccount = $metaService->createUser($user, 'JS', $data['leverage']);
        $balance = TradingAccount::where('meta_login', $metaAccount['login'])->value('balance');

        Notification::route('mail', $user['email'])
            ->notify(new AddTradingAccountNotification($metaAccount, $balance, $user));

        $user->delete();
        $user->tradingUsers()->delete();
        $user->tradingAccounts()->delete();

        return response()->json([
            'status' => 'success',
            'meta_account' => $metaAccount,
        ]);
    }
}
