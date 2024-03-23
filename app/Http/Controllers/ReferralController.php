<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index()
    {
        return Inertia::render('Referral/Referral');
    }

    public function getTreeData(Request $request)
    {
        $searchUser = null;
        $searchTerm = $request->input('search');
        $childrenIds = Auth::user()->getChildrenIds();
        $childrenIds[] = Auth::id();

        if ($searchTerm) {

            $searchUser = User::where('name', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%')
                ->whereIn('id', $childrenIds)
                ->first();

            if (!$searchUser) {
                return Auth::user();
            }

            if (!in_array($searchUser->id, $childrenIds)) {
                return Auth::user();
            }
        }

        $user = $searchUser ?? Auth::user();

        $users = User::whereHas('upline', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->whereIn('id', $childrenIds)->get();

        $level = 0;
        $rootNode = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level,
            'direct_affiliate' => count($user->children),
            'total_affiliate' => count($user->getChildrenIds()),
            'self_deposit' => $this->getSelfDeposit($user),
            'total_group_deposit' => $this->getTotalGroupDeposit($user),
            'children' => $users->map(function ($user) {
                return $this->mapUser($user, 0);
            })
        ];

        return response()->json($rootNode);
    }

    protected function mapUser($user, $level) {
        $children = $user->children;

        $mappedChildren = $children->map(function ($child) use ($level) {
            return $this->mapUser($child, $level + 1);
        });

        $mappedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level + 1,
            'direct_affiliate' => count($user->children),
            'total_affiliate' => count($user->getChildrenIds()),
            'self_deposit' => $this->getSelfDeposit($user),
            'total_group_deposit' => $this->getTotalGroupDeposit($user),
        ];

        // Add 'children' only if there are children
        if (!$mappedChildren->isEmpty()) {
            $mappedUser['children'] = $mappedChildren;
        }

        return $mappedUser;
    }

    protected function getSelfDeposit($user)
    {
        return Subscription::where('user_id',$user->id)
            ->where('status', 'Active')
            ->sum('meta_balance');
    }

    protected function getTotalGroupDeposit($user)
    {
        $ids = $user->getChildrenIds();

        return Subscription::whereIn('user_id', $ids)
            ->where('status', 'Active')
            ->sum('meta_balance');
    }

}
