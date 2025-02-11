<?php

namespace App\Http\Controllers;

use App\Models\CopyTradeTransaction;
use App\Models\Country;
use App\Models\PammSubscription;
use App\Models\SubscriptionBatch;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Subscriber;
use App\Models\SettingRank;
use App\Models\Transaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\SelectOptionService;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $locale = app()->getLocale();

        if ($searchTerm) {
            // Search for a user by name or email
            $searchUser = User::where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%');
            })
                ->whereIn('id', $childrenIds) // Ensure the user is within the children IDs
                ->first();

            // If no user is found or user is not a child, fallback to the authenticated user
            if (!$searchUser || !in_array($searchUser->id, $childrenIds)) {
                $user = Auth::user();
            } else {
                $user = $searchUser;
            }
        } else {
            // If no search term, use the authenticated user
            $user = Auth::user();
        }

        $users = User::whereHas('upline', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->whereIn('id', $childrenIds)->get();

        $rank = SettingRank::where('id', $user->display_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        $level = 0;
        $rootNode = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level,
            'rank' => $translations[$locale] ?? '-',
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
        $locale = app()->getLocale();

        $rank = SettingRank::where('id', $user->display_rank_id)->first();

        // Parse the JSON data in the name column to get translations
        $translations = json_decode($rank->name, true);

        $mappedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => $level + 1,
            'rank' => $translations[$locale] ?? '-',
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
        $subscriptions = Subscription::where('user_id', $user->id)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $pamm = PammSubscription::where('user_id', $user->id)
            ->where('status', 'Active')
            ->sum('subscription_amount');

        return $subscriptions + $pamm;
    }

    protected function getTotalGroupDeposit($user)
    {
        $ids = $user->getChildrenIds();

        $subscriptions = Subscription::whereIn('user_id', $ids)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $pamm = PammSubscription::whereIn('user_id', $ids)
            ->where('status', 'Active')
            ->sum('subscription_amount');

        return $subscriptions + $pamm;
    }

    public function affiliateSubscription()
    {
        return Inertia::render('Referral/AffiliateSubscriptions/AffiliateSubscriptions');
    }

    public function getAffiliateCopyTrade(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $childrenIds = Auth::user()->getChildrenIds();

            $query = SubscriptionBatch::with([
                'user:id,username,upline_id,hierarchyList',
                'master',
                'master.tradingUser'
            ])
                ->where('status', 'Active')
                ->whereIn('user_id', $childrenIds);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where('username', 'like', '%' . $keyword . '%');
                    })
                        ->orWhereHas('master', function ($query) use ($keyword) {
                            $query->whereHas('tradingUser', function ($query) use ($keyword) {
                                $query->where('name', 'like', '%' . $keyword . '%')
                                    ->orWhere('company', 'like', '%' . $keyword . '%');
                            });
                        })
                        ->orWhere('meta_login', 'like', '%' . $keyword . '%')
                        ->orWhere('master_meta_login', 'like', '%' . $keyword . '%');
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('id', $usersUnderLeader);
            }

            $masterId = $data['filters']['master_id']['value']['id'] ?? null;

            if ($masterId) {
                $query->where('master_id', $masterId);
            }

            if (!empty($data['filters']['start_join_date']['value']) && !empty($data['filters']['end_join_date']['value'])) {
                $start_join_date = Carbon::parse($data['filters']['start_join_date']['value'])->addDay()->startOfDay();
                $end_join_date = \Carbon\Carbon::parse($data['filters']['end_join_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('approval_date', [$start_join_date, $end_join_date]);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('approval_date');
            }

            $activeCopyTrade = (clone $query)->get()->sum('meta_balance');
            $totalAffiliate = (clone $query)
                ->distinct('user_id')
                ->count();

            $affiliates = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $affiliates,
                'totalAffiliate' => $totalAffiliate,
                'totalDeposit' => $activeCopyTrade
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function getAffiliatePamm(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $childrenIds = Auth::user()->getChildrenIds();

            $query = PammSubscription::with([
                'user:id,username,upline_id,hierarchyList',
                'master',
                'master.tradingUser'
            ])
                ->where([
                    'type' => $data['filters']['pamm_type']['value'],
                    'status' => 'Active',
                ])
                ->whereIn('user_id', $childrenIds);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where('username', 'like', '%' . $keyword . '%');
                    })
                        ->orWhereHas('master', function ($query) use ($keyword) {
                            $query->whereHas('tradingUser', function ($query) use ($keyword) {
                                $query->where('name', 'like', '%' . $keyword . '%')
                                    ->orWhere('company', 'like', '%' . $keyword . '%');
                            });
                        })
                        ->orWhere('meta_login', 'like', '%' . $keyword . '%')
                        ->orWhere('master_meta_login', 'like', '%' . $keyword . '%');
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('id', $usersUnderLeader);
            }

            if (!empty($data['filters']['start_join_date']['value']) && !empty($data['filters']['end_join_date']['value'])) {
                $start_join_date = Carbon::parse($data['filters']['start_join_date']['value'])->addDay()->startOfDay();
                $end_join_date = \Carbon\Carbon::parse($data['filters']['end_join_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_join_date, $end_join_date]);
            }

            $masterId = $data['filters']['master_id']['value']['id'] ?? null;

            if ($masterId) {
                $query->where('master_id', $masterId);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            $activePamm = (clone $query)->get()->sum('subscription_amount');
            $totalAffiliate = (clone $query)
                ->distinct('user_id')
                ->count();

            $affiliates = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $affiliates,
                'totalAffiliate' => $totalAffiliate,
                'totalDeposit' => $activePamm
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function affiliateListing()
    {
        return Inertia::render('Referral/AffiliateListing/AffiliateListing');
    }

    public function affiliateListingData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $childrenIds = Auth::user()->getChildrenIds();

            $query = User::with([
                'rank',
                'userCountry'
            ])
                ->withSum('active_copy_trade', 'meta_balance')
                ->withSum('active_pamm', 'subscription_amount')
                ->whereIn('id', $childrenIds);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->where('username', 'like', '%' . $keyword . '%');
                });
            }

            $leaderId = $data['filters']['leader_id']['value']['id'] ?? null;

            if ($leaderId) {
                // Load users under the specified leader
                $usersUnderLeader = User::where('id', $leaderId)
                    ->orWhere('hierarchyList', 'like', "%-$leaderId-%")
                    ->pluck('id');

                $query->whereIn('id', $usersUnderLeader);
            }

            if (!empty($data['filters']['start_join_date']['value']) && !empty($data['filters']['end_join_date']['value'])) {
                $start_join_date = Carbon::parse($data['filters']['start_join_date']['value'])->addDay()->startOfDay();
                $end_join_date = \Carbon\Carbon::parse($data['filters']['end_join_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_join_date, $end_join_date]);
            }

            if ($data['filters']['country']['value']) {
                $query->where('country', $data['filters']['country']['value']);
            }

            if ($data['filters']['rank']['value']) {
                $query->where('display_rank_id', $data['filters']['rank']['value']);
            }

            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            $activeCopyTrade = (clone $query)->get()->sum('active_copy_trade_sum_meta_balance');
            $activePamm = (clone $query)->get()->sum('active_pamm_sum_subscription_amount');

            $affiliates = $query->paginate($data['rows']);

            $totalAffiliate = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $affiliates,
                'totalAffiliate' => $totalAffiliate,
                'totalDeposit' => $activeCopyTrade + $activePamm
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
