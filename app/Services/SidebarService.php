<?php

namespace App\Services;

use App\Models\ApplicationForm;
use App\Models\Master;
use App\Models\User;
use Auth;

class SidebarService {
    public function getMasterVisibility(): bool
    {
        $user = \Auth::user();

        $pammMasters = Master::where('category', 'pamm')
            ->get();

        if (!empty($pammMasters) || !empty($pammMasters->not_visible_to)) {
            foreach ($pammMasters as $master) {
                $leaderIds = json_decode($master->not_visible_to, true);

                if ($leaderIds) {
                    foreach ($leaderIds as $leaderId) {
                        $leader = User::find($leaderId);

                        $childrenIds = $leader->getChildrenIds();
                        $childrenIds[] = $leaderId;

                        if ($user && in_array($user->id, $childrenIds)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    public function getSidebarContentVisibility(): bool
    {
        $user = User::find(1137);

        if ($user) {
            $childrenIds = $user->getChildrenIds();

            $authUserId = \Auth::id();

            if ($authUserId == $user->id || in_array($authUserId, $childrenIds)) {
                return false;
            }

        }

        // Otherwise, return true
        return true;
    }

    public function canAccessApplication(): bool
    {
        $authUser = Auth::user();
        $first_leader = $authUser->getFirstLeader();

        $query = ApplicationForm::with('leaders')
            ->where('status', 'Active');

        if (empty($first_leader)) {
            $query->whereHas('leaders', function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id);
            });
        } else {
            $query->whereHas('leaders', function ($q) use ($first_leader) {
                $q->where('user_id', $first_leader->id);
            });
        }

        // Return true if any matching application form exists
        return $query->exists();
    }
}
