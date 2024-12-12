<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTypeToLeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_type_id',
        'user_id',
    ];
}
