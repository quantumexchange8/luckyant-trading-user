<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionsSchedulerLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'old_subscription_id',
        'new_subscription_id',
        'old_expired_date',
        'new_expired_date',
        'old_status',
        'new_status',
    ];

    protected $casts = [
        'old_expired_date' => 'datetime',
        'new_expired_date' => 'datetime',
    ];
}
