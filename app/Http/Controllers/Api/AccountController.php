<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TradingAccount;
use App\Models\TradingUser;
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
            ->where('name', $data['name'])
            ->first();

        $originalEmail = $data['email'];

        if (empty($user)) {
            // Email already exists, create a new unique email by adding a suffix
            $baseEmail = $originalEmail;
            $index = 1;
            $user_email = $baseEmail . '-' . $index;

            // Check if the new email with a suffix already exists and increment the suffix if needed
            while (User::withTrashed()->where('email', $user_email)->exists()) {
                $index++;
                $user_email = $baseEmail . '-' . $index;
            }

            // Create the new user with the unique email, but store the original email separately
            $user = User::create([
                'name' => $data['name'],
                'email' => $user_email,  // Store the modified email with the suffix
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

        // Use the original email for createUser and notifications
        $metaAccount = $metaService->createUser($user, 'JS', $data['leverage'], $originalEmail);
        $balance = TradingAccount::where('meta_login', $metaAccount['login'])->value('balance');

        Notification::route('mail', $originalEmail)
            ->notify(new AddTradingAccountNotification($metaAccount, $balance, $user));

        if ($user->remark == 'china_pamm') {
            $user->delete();
        }

        TradingAccount::where('meta_login', $metaAccount['login'])->delete();
        TradingUser::where('meta_login', $metaAccount['login'])->delete();

        return response()->json([
            'status' => 'success',
            'meta_account' => $metaAccount,
        ]);
    }
}
