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
        $user = User::with('media')
            ->withCount('children')
            ->find(Auth::id());

        $root = [
            'id' => $user->id,
            'username' => $user->username,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'email' => $user->email,
            'level' => 0,
            'rank' => $user->rank->name,
            'direct_affiliate' => $user->children_count,
            'total_affiliate' => count($user->getChildrenIds()),
            'self_deposit' => $this->getSelfDeposit($user),
            'total_group_deposit' => $this->getTotalGroupDeposit($user),
        ];

        return Inertia::render('Referral/Referral', [
            'root' => $root,
        ]);
    }

    public function getTreeData(Request $request)
    {
        $authUser = Auth::user();
        $childrenIds = $authUser->getChildrenIds();
        $childrenIds[] = $authUser->id;

        // Handle Search
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $searchUser = User::with(['children', 'media', 'rank'])
                ->where(function ($query) use ($searchTerm) {
                    $query->where('username', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                })
                ->whereIn('id', $childrenIds)
                ->first();

            if (!$searchUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $searchUser->id,
                    'username' => $searchUser->username,
                    'profile_photo' => $searchUser->getFirstMediaUrl('profile_photo'),
                    'email' => $searchUser->email,
                    'level' => $searchUser->id == $authUser->id
                        ? 0
                        : $this->calculateLevel($searchUser->hierarchyList),
                    'rank' => $searchUser->rank?->name ?? 'N/A',
                    'direct_affiliate' => $searchUser->children()->count(),
                    'total_affiliate' => count($searchUser->getChildrenIds()),
                    'self_deposit' => $this->getSelfDeposit($searchUser),
                    'total_group_deposit' => $this->getTotalGroupDeposit($searchUser),
                    'has_children' => $searchUser->children()->exists(),
                ]
            ]);
        }

        // Load direct children of child_id node
        $childId = $request->input('child_id');
        $parent = User::with(['children', 'media', 'rank'])->findOrFail($childId);

        $directs = $parent->children->map(function ($user) use ($authUser) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
                'email' => $user->email,
                'level' => $user->id == $authUser->id
                    ? 0
                    : $this->calculateLevel($user->hierarchyList),
                'rank' => $user->rank?->name ?? 'N/A',
                'direct_affiliate' => $user->children()->count(),
                'total_affiliate' => count($user->getChildrenIds()),
                'self_deposit' => $this->getSelfDeposit($user),
                'total_group_deposit' => $this->getTotalGroupDeposit($user),
                'has_children' => $user->children()->exists(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $directs,
        ]);
    }

    private function calculateLevel($hierarchyList)
    {
        if (is_null($hierarchyList) || $hierarchyList === '') {
            return 0;
        }

        $split = explode('-' . Auth::id() . '-', $hierarchyList);
        if (count($split) < 2) {
            return 0;
        }

        return substr_count($split[1], '-') + 1;
    }

    protected function getSelfDeposit($user)
    {
        $subscriptions = Subscription::where('user_id', $user->id)
            ->where('status', 'Active')
            ->sum('meta_balance');

        $pamm = PammSubscription::with('master')
            ->whereHas('master', function ($q) {
                $q->where('involves_world_pool', 1);
            })
            ->where('user_id', $user->id)
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

        $pamm = PammSubscription::with('master')
            ->whereHas('master', function ($q) {
                $q->where('involves_world_pool', 1);
            })
            ->whereIn('user_id', $ids)
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
                'user:id,username,email,upline_id,hierarchyList',
                'master',
                'master.tradingUser'
            ])
                ->where('status', 'Active')
                ->whereIn('user_id', $childrenIds);

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where('username', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
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
                'user:id,username,email,upline_id,hierarchyList',
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
                        $query->where('username', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
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

            $activePamm = (clone $query)
                ->whereHas('master', function ($q) {
                    $q->where('involves_world_pool', 1);
                })
                ->get()
                ->sum('subscription_amount');
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
                    $q->where('username', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
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
