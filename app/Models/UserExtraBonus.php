<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserExtraBonus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_type_id',
        'extra_bonus',
    ];
}
