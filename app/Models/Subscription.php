<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'transaction_id',
        'master_id',
        'type',
        'subscription_number',
        'subscription_period',
        'subscription_fee',
        'next_pay_date',
        'status',
        'handle_by',
    ];

    protected $casts = [
        'next_pay_date' => 'date',
    ];
}
