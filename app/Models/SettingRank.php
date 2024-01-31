<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingRank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'position',
        'standard_lot',
        'package_requirement',
        'direct_referral',
        'cultivate_type',
        'cultivate_member',
        'group_sales',
        'rebate',
    ];
}
