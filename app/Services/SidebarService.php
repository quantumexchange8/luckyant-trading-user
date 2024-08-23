<?php

namespace App\Services;

use App\Models\Master;
use App\Models\User;

class SidebarService {
    public function getMasterVisibility(): bool
    {
        $user = \Auth::user();

        $pammMasters = Master::where('category', 'pamm')
            ->get();

        if (!empty($pammMasters) || !empty($pammMasters->not_visible_to)) {
            foreach ($pammMasters as $master) {
                $leaderIds = json_decode($master->not_visible_to, true);

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
        return true;
    }
}
