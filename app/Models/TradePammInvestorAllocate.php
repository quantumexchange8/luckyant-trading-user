<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradePammInvestorAllocate extends Model
{
    use SoftDeletes;

    protected $table = 'trade_pamm_investor_allocate';

    protected $guarded = ['id'];

    public function pamm_subscriptions(): HasMany
    {
        return $this->hasMany(PammSubscription::class, 'id', 'subscription_id');
    }
}
