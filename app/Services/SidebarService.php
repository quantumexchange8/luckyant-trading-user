<?php

namespace App\Services;

use App\Models\Master;
use App\Models\User;

class SidebarService {
    public function getMasterVisibility(): bool
    {
        $user = \Auth::user();

        $pammMasters = Master::where('category', 'pamm')
            ->where('type', 'ESG')
            ->get();

        if (!empty($pammMasters)) {
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
