<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CopyTradeTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'subscription_id',
        'master_id',
        'master_meta_login',
        'amount',
        'real_fund',
        'demo_fund',
        'type',
        'status',
    ];
}
