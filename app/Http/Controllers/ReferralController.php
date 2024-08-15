<?php

namespace App\Http\Controllers;

use App\Models\CopyTradeTransaction;
use App\Models\Country;
use App\Models\PammSubscription;
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
            'rank' => $translations[$locale] ?? $rank->name,
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
            'rank' => $translations[$locale] ?? $rank->name,
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
        $locale = app()->getLocale();

        $rankLists = SettingRank::all()->map(function ($rank) use ($locale) {
            $translations = json_decode($rank->name, true);
            $label = $translations[$locale] ?? $rank->name;
            return [
                'value' => $rank->id,
                'label' => $label,
            ];
        })->prepend(['value' => '', 'label' => trans('public.all')]);

        return Inertia::render('Referral/AffiliateSubscriptions', [
            'rankLists' => $rankLists,
            'countries' => (new SelectOptionService())->getCountries(),
        ]);
    }

    public function affiliateSubscriptionData(Request $request)
    {
        $user = Auth::user();

        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $query = CopyTradeTransaction::query()
            ->with(['tradingUser:meta_login,name,company', 'master', 'master.tradingUser', 'user:id,username'])
            ->whereIn('user_id', $user->getChildrenIds());

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('master.tradingUser', function ($user) use ($search) {
                    $user->where('name', 'like', $search)
                        ->orWhere('meta_login', 'like', $search)
                        ->orWhere('company', 'like', $search);
                })->orWhereHas('tradingUser', function ($to_wallet) use ($search) {
                        $to_wallet->where('name', 'like', $search)
                            ->orWhere('company', 'like', $search);
                    })->orWhereHas('user', function ($to_wallet) use ($search) {
                        $to_wallet->where('username', 'like', $search);
                    });
            })
                ->orWhere('meta_login', 'like', $search);
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $dateRange = explode(' - ', $date);
            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $totalAccounts = Subscriber::whereIn('user_id', $user->getChildrenIds())->where('status', 'Subscribing')->count();
        $totalAmount = $query->sum('amount');

        if ($column == 'user_username') {
            $results = $query->join('users', 'copy_trade_transactions.subscription_id', '=', 'users.id')
                ->orderBy('users.username', $sortOrder)
                ->paginate($request->input('paginate', 10));
        } else {
            $results = $query
                ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
                ->paginate($request->input('paginate', 10));
        }

        return response()->json([
            'affiliateCopyTradeTransactions' => $results,
            'totalAccounts' => $totalAccounts,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function affiliateListing()
    {
        $locale = app()->getLocale();

        $rankLists = SettingRank::all()->map(function ($rank) use ($locale) {
            $translations = json_decode($rank->name, true);
            $label = $translations[$locale] ?? $rank->name;
            return [
                'value' => $rank->id,
                'label' => $label,
            ];
        })->prepend(['value' => '', 'label' => trans('public.all')]);

        return Inertia::render('Referral/AffiliateListing', [
            'rankLists' => $rankLists,
            'countries' => (new SelectOptionService())->getCountries(),
        ]);
    }

    public function affiliateListingData(Request $request)
    {
        $user = Auth::user();
        $columnName = $request->input('columnName'); // Retrieve encoded JSON string
        // Decode the JSON
        $decodedColumnName = json_decode(urldecode($columnName), true);

        $column = $decodedColumnName ? $decodedColumnName['id'] : 'created_at';
        $sortOrder = $decodedColumnName ? ($decodedColumnName['desc'] ? 'desc' : 'asc') : 'desc';

        $downlineIds = $user->getChildrenIds();

        $downlineUsers = User::whereIn('id', $downlineIds)
            ->with(['rank:id,name', 'userCountry:id,name,translations'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('username', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('date'), function ($query) use ($request) {
                $date = $request->input('date');
                $dateRange = explode(' - ', $date);
                $start_date = Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->when($request->filled('rank'), function ($query) use ($request) {
                $rank_id = $request->input('rank');
                $query->where(function ($innerQuery) use ($rank_id) {
                    $innerQuery->where('display_rank_id', $rank_id);
                });
            })
            ->when($request->filled('country'), function ($query) use ($request) {
                $country_id = $request->input('country');
                $query->where(function ($innerQuery) use ($country_id) {
                    $innerQuery->where('country', $country_id);
                });
            });

        $totalAffiliate = $downlineUsers->count();
        $transactionQueryIds = $downlineUsers->pluck('id');

        $results = $downlineUsers
            ->orderBy($column == null ? 'created_at' : $column, $sortOrder)
            ->paginate($request->input('paginate', 10));

        $locale = app()->getLocale();

        $results->each(function ($downlineUser) use ($locale) {
            $rank = $downlineUser->rank;
            $translations = json_decode($rank->name, true);
            $downlineUser->affiliate_rank = $translations[$locale] ?? $rank->name;

            $country = $downlineUser->userCountry;
            $countryName = json_decode($country->translations, true);
            $downlineUser->affiliate_country = $countryName[$locale] ?? $country->name;

            $subscriptionAmount = Subscription::where('user_id', $downlineUser->id)->where('status', 'Active')->sum('meta_balance');
            $downlineUser->subscription_amount = $subscriptionAmount;
        });

        $totalDeposit = Subscription::whereIn('user_id', $transactionQueryIds)
            ->where('status', 'Active')
            ->sum('meta_balance');

        return response()->json([
            'downlineUsers' => $results,
            'totalAffiliate' => $totalAffiliate,
            'totalDeposit' => $totalDeposit
        ]);
    }

    public function getAllCountries(Request $request)
    {
        $locale = app()->getLocale();

        $countries = Country::query()
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('translations', 'like', "%{$search}%");
                });
            })
            ->select('id', 'name', 'translations')
            ->get()
            ->map(function ($country) use ($locale) {
                $translations = json_decode($country->translations, true);
                $label = $translations[$locale] ?? $country->name;
                return [
                    'id' => $country->id,
                    'name' => $label,
                ];
            });

        return response()->json($countries);
    }
}
