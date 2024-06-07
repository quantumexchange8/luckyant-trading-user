<?php

namespace App\Http\Controllers;

use App\Models\Master;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PammController extends Controller
{
    public function pamm_listing()
    {
        return Inertia::render('Pamm/PammListing/PammListing');
    }

    public function getPammMasters(Request $request)
    {
        $user = Auth::user();
        $first_leader = $user->getFirstLeader();

        $masterAccounts = Master::with([
            'user:id,username,name,email',
            'tradingAccount:id,meta_login,balance,equity',
            'tradingUser:id,name,company',
            'masterManagementFee'
        ])
            ->where('status', 'Active')
            ->where('signal_status', 1)
            ->where('type', 'PAMM')
            ->whereNot('user_id', $user->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->input('search') . '%';
                $query->whereHas('tradingAccount', function ($q) use ($search) {
                    $q->where('meta_login', 'like', $search);
                })
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', $search)
                            ->orWhere('username', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->input('type');
                switch ($type) {
                    case 'max_equity':
                        $query->orderByDesc('min_join_equity');
                        break;
                    case 'min_equity':
                        $query->orderBy('min_join_equity');
                        break;
                    case 'max_sub':
                        $query->withCount('subscribers')->orderByDesc('subscribers_count');
                        break;
                    case 'min_sub':
                        $query->withCount('subscribers')->orderBy('subscribers_count');
                        break;
                    // Add more cases as needed for other 'type' values
                }
            });

        if ($user->is_public == 0 && $first_leader) {
            $leader = $first_leader;
            while ($leader && $leader->masterAccounts->isEmpty()) {
                $leader = $leader->getFirstLeader();
            }

            if ($leader) {
                $masterAccounts = $masterAccounts
                    ->where('is_public', $leader->is_public)
                    ->whereIn('user_id', $leader->masterAccounts->pluck('user_id'));
            } else {
                // If leader is null, reset $masterAccounts to an empty query
                $masterAccounts = $masterAccounts->where('id', null);
            }
        } elseif ($user->is_public == 1 && $first_leader) {
            $masterAccounts->where('is_public', $first_leader->is_public);
        } else {
            if ($user->is_public == 0) {
                $masterAccounts->where('is_public', $user->is_public)
                    ->whereIn('id', $user->masterAccounts->pluck('id'));
            } else {
                $masterAccounts->where('is_public', $user->is_public);
            }
        }

        $masterAccounts = $masterAccounts->latest()->paginate(10);

        $masterAccounts->each(function ($master) {
            $totalSubscriptionsFee = Subscription::where('master_id', $master->id)
                ->where('status', 'Active')
                ->sum('meta_balance');

            $master->user->profile_photo_url = $master->user->getFirstMediaUrl('profile_photo');
            $master->totalFundWidth = $master->total_fund == 0 ? $totalSubscriptionsFee + $master->extra_fund : (($totalSubscriptionsFee + $master->extra_fund) / $master->total_fund) * 100 ;
        });

        return response()->json($masterAccounts);
    }
}
