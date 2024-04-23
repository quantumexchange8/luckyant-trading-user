<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'meta_balance',
        'real_fund',
        'demo_fund',
        'master_id',
        'master_meta_login',
        'type',
        'subscriber_id',
        'subscription_number',
        'subscription_period',
        'subscription_fee',
        'termination_date',
        'settlement_date',
        'status',
        'auto_renewal',
        'approval_date',
        'remarks',
        'claimed_profit',
        'handle_by',
    ];

    protected $casts = [
        'termination_date' => 'datetime',
        'settlement_date' => 'datetime',
        'approval_date' => 'datetime',
    ];
}
