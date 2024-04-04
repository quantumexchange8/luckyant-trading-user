<?php

namespace App\Http\Controllers;

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

        $rank = SettingRank::where('id', $user->setting_rank_id)->first();

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

        $rank = SettingRank::where('id', $user->setting_rank_id)->first();

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

    public function affiliateSubscription()
    {
        return Inertia::render('Referral/AffiliateSubscriptions');
    }

    public function affiliateSubscriptionData()
    {
        $user = Auth::user();
        
        $downlineIds = $user->getChildrenIds();
        
        $downlineUsers = User::whereIn('id', $downlineIds)->get();
        
        $transactions = [];
        $transactionIds = []; // Array to keep track of transaction IDs
        
        $subscribers = Subscriber::with('subscription')
            ->whereIn('user_id', $downlineUsers->pluck('id'))
            ->where('status', 'Subscribing')
            ->whereNotNull('approval_date')
            ->get();
        
        foreach ($subscribers as $subscriber) {
            $subscription = $subscriber->subscription;
            
            if ($subscription) {
                // Retrieve transactions related to active subscription
                if ($subscription->status === 'Active') {
                    $subscriberTransactions = Transaction::with('to_meta_login.ofUser.upline', 'from_meta_login.ofUser.upline')
                        ->where(function ($query) use ($subscriber, $subscription) {
                            $query->where('from_meta_login', $subscription->meta_login)
                                ->orWhere('to_meta_login', $subscription->meta_login);
                        })
                        ->where('user_id', $subscriber->user_id)
                        ->where('category', 'trading_account')
                        ->whereNot('transaction_type', 'Withdrawal')
                        ->where('created_at', '>', $subscription->created_at)
                        ->where('status', 'Success')
                        ->when(request()->filled('search'), function ($query) {
                            $search = '%' . request()->input('search') . '%';
                            $query->where(function ($query) use ($search) {
                                $query->orWhereHas('from_meta_login.ofUser', function ($subQuery) use ($search) {
                                    $subQuery->where('meta_login', 'like', $search)
                                        ->orWhere('username', 'like', $search)
                                        ->orWhere('email', 'like', $search);
                                })->orWhereHas('to_meta_login.ofUser', function ($subQuery) use ($search) {
                                    $subQuery->where('meta_login', 'like', $search)
                                        ->orWhere('username', 'like', $search)
                                        ->orWhere('email', 'like', $search);
                                });
                            });
                        })
                        ->when(request()->filled('date'), function ($query) {
                            $dateRange = explode(' - ', request()->input('date'));
                            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                            $end_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                            $query->whereBetween('created_at', [$start_date, $end_date]);
                        })
                        ->get();
                } elseif (in_array($subscription->status, ['Terminated', 'Expired'])) {
                    // Retrieve transactions related to terminated or expired subscription
                    $subscriberTransactions = Transaction::with('to_meta_login.ofUser.upline', 'from_meta_login.ofUser.upline')
                        ->where(function ($query) use ($subscriber, $subscription) {
                            $query->where('from_meta_login', $subscription->meta_login)
                                ->orWhere('to_meta_login', $subscription->meta_login);
                        })
                        ->where('user_id', $subscriber->user_id)
                        ->where('category', 'trading_account')
                        ->whereNot('transaction_type', 'Withdrawal')
                        ->whereBetween('created_at', [$subscription->created_at, $subscription->updated_at])
                        ->where('status', 'Success')
                        ->when(request()->filled('search'), function ($query) {
                            $search = '%' . request()->input('search') . '%';
                            $query->where(function ($query) use ($search) {
                                $query->orWhereHas('from_meta_login.ofUser', function ($subQuery) use ($search) {
                                    $subQuery->where('meta_login', 'like', $search)
                                        ->orWhere('username', 'like', $search)
                                        ->orWhere('email', 'like', $search);
                                })->orWhereHas('to_meta_login.ofUser', function ($subQuery) use ($search) {
                                    $subQuery->where('meta_login', 'like', $search)
                                        ->orWhere('username', 'like', $search)
                                        ->orWhere('email', 'like', $search);
                                });
                            });
                        })
                        ->when(request()->filled('date'), function ($query) {
                            $dateRange = explode(' - ', request()->input('date'));
                            $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                            $end_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                            $query->whereBetween('created_at', [$start_date, $end_date]);
                        })
                        ->get();
                }
                
                foreach ($subscriberTransactions as $transaction) {
                    $transactionData = $transaction->toArray();
                    
                    // Check if the transaction ID is not already processed
                    if (!in_array($transactionData['id'], $transactionIds)) {
                        $transactionIds[] = $transactionData['id'];
                        
                        $masterRecord = $subscriber->master ?? $subscription->master ?? null;
                        
                        if ($masterRecord) {
                            $masterRecordWithTradingUser = $masterRecord->load('tradingUser');
                            $transactionData['master_record'] = $masterRecordWithTradingUser->toArray();
                        }
                        
                        $transactions[] = $transactionData;
                    }
                }
            }
        }
        
        // Retrieve SubscriptionFee transactions
        $subscriptionFees = Transaction::with('to_meta_login.ofUser.upline', 'from_meta_login.ofUser.upline')
            ->whereIn('id', Subscription::whereIn('id', $subscribers->pluck('subscription_id'))->pluck('transaction_id'))
            ->where('transaction_type', 'SubscriptionFee')
            ->where('status', 'Success')
            ->when(request()->filled('search'), function ($query) {
                $search = '%' . request()->input('search') . '%';
                $query->where(function ($query) use ($search) {
                    $query->orWhereHas('from_meta_login.ofUser', function ($subQuery) use ($search) {
                        $subQuery->where('meta_login', 'like', $search)
                            ->orWhere('username', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    })->orWhereHas('to_meta_login.ofUser', function ($subQuery) use ($search) {
                        $subQuery->where('meta_login', 'like', $search)
                            ->orWhere('username', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
                });
            })
            ->when(request()->filled('date'), function ($query) {
                $dateRange = explode(' - ', request()->input('date'));
                $start_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[0])->startOfDay();
                $end_date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->get();
        
        foreach ($subscriptionFees as $subscriptionFee) {
            $transactionData = $subscriptionFee->toArray();
            
            // Check if the transaction ID is not already processed
            if (!in_array($transactionData['id'], $transactionIds)) {
                $transactionIds[] = $transactionData['id'];
                
                $subscription = Subscription::where('transaction_id', $subscriptionFee->id)->first();
                if ($subscription) {
                    $masterRecord = $subscription->master ?? null;
                    if ($masterRecord) {
                        $masterRecordWithTradingUser = $masterRecord->load('tradingUser');
                        $transactionData['master_record'] = $masterRecordWithTradingUser->toArray();
                    }
                }
                
                $transactions[] = $transactionData;
            }
        }
        
        // Sort transactions by created_at in descending order
        usort($transactions, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Paginate the transactions (assuming 10 items per page)
        $perPage = 10;
        $currentPage = request()->has('page') ? request()->query('page') : 1;
        $pagedData = array_slice($transactions, ($currentPage - 1) * $perPage, $perPage);
        $paginatedData = new LengthAwarePaginator($pagedData, count($transactions), $perPage, $currentPage);
        
        return response()->json($paginatedData);
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
        });
        // Calculate total deposit amount for downline users
        $totalAffiliate = count(Auth::user()->getChildrenIds());
        $totalDeposit = Subscription::whereIn('user_id', Auth::user()->getChildrenIds())
                        ->where('status', 'Active')
                        ->sum('meta_balance');

        return Inertia::render('Referral/AffiliateListing', [
            'rankLists' => $rankLists,
            'totalAffiliate' => $totalAffiliate,
            'totalDeposit' => $totalDeposit,
        ]);
    }

    public function affiliateListingData(Request $request)
    {
        $user = Auth::user();
            
        $downlineIds = $user->getChildrenIds();
        
        $downlineUsers = User::whereIn('id', $downlineIds)
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
                    $innerQuery->where('setting_rank_id', $rank_id);
                });
            })
            ->latest()
            ->paginate(10);
    
        $locale = app()->getLocale();

        $countries = (new SelectOptionService())->getCountries();
    
        $downlineUsers->each(function ($downlineUser) use ($locale, $countries) {
            $rank = SettingRank::find($downlineUser->setting_rank_id);
            // Parse the JSON data in the name column to get translations
            $translations = json_decode($rank->name, true);
    
            // Add the translated rank name to the downline user object
            $downlineUser->rank = $translations[$locale] ?? $rank->name;

            // Subtract 1 from the country code
            $countryCode = $downlineUser->country - 1;
            // Get the country name from the countries array based on the modified country code
            $countryName = isset($countries[$countryCode]) ? $countries[$countryCode]['label'] : 'Unknown';
            // Replace the country code with the country name
            $downlineUser->country = $countryName;
            
        });
    
        return response()->json($downlineUsers);
    }
}