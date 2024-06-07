<?php

namespace App\Services;

use App\Models\MasterLeader;
use App\Models\User;

class SidebarService {
    public function hasPammMasters()
    {
        $user = \Auth::user();

        $pammMasters = MasterLeader::all();

        foreach ($pammMasters as $master) {
            $leader = User::find($master->leader_id);

            if ($leader) {
                $childrenIds = $leader->getChildrenIds();

                if ($user && in_array($user->id, $childrenIds)) {
                    return true;
                }
            }
        }

        return false;
    }
}
