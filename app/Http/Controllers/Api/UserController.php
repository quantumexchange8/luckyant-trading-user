<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
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
                    'email' => $data['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('lucky1234.'),
                    'remember_token' => Str::random(10),
                    'country' => $data['country_id'],
                    'nationality' => $data['nationality'],
                    'phone' => '+' . $data['phone_number'],
                    'dob' => $data['dob'] ?? null,
                    'remark' => 'luckymall',
                    'status' => 'Inactive',
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

        if ($data)
        $user = User::withTrashed()
            ->where('email', $data['user']['email'])
            ->where('name', $data['user']['name'])
            ->first();

        $originalEmail = $data['user']['email'];

        \Log::info($originalEmail);

        return response()->json([
            'success' => true,
        ]);
    }
}
