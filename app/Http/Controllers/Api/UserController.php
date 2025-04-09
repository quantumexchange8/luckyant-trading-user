<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function authorize_user_account(Request $request)
    {
        $username = $request->username;
        $user = User::firstWhere('username', $username);

        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'User account already exists',
                'user' => $user,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Username not found',
            ]);
        }
    }

    public function sync_user_account(Request $request)
    {
        $data = $request->user;

        if (!empty($data)) {
            $user = User::firstWhere([
                'username' => $data['username'],
            ]);

            if (empty($user)) {
                $user = User::create([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('lucky1234.'),
                    'remember_token' => Str::random(10),
                    'country' => $data['country_id'],
                    'nationality' => $data['nationality'],
                    'dial_code' => '+' . $data['dial_code'],
                    'phone' => '+' . $data['phone_number'],
                    'dob' => $data['dob'] ?? '1990-01-01',
                    'remark' => 'luckymall',
                    'status' => 'Active',
                ]);

                Wallet::create([
                    'user_id' => $user->id,
                    'name' => 'Cash Wallet',
                    'type' => 'cash_wallet',
                    'wallet_address' => RunningNumberService::getID('cash_wallet'),
                ]);

                Wallet::create([
                    'user_id' => $user->id,
                    'name' => 'Bonus Wallet',
                    'type' => 'bonus_wallet',
                    'wallet_address' => RunningNumberService::getID('bonus_wallet'),
                ]);

                Wallet::create([
                    'user_id' => $user->id,
                    'name' => 'E-Wallet',
                    'type' => 'e_wallet',
                    'wallet_address' => RunningNumberService::getID('e_wallet'),
                ]);
            }

            $user->update([
                'sync_mall' => true,
            ]);

            return response()->json([
                'success' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No data found'
        ]);
    }
}
