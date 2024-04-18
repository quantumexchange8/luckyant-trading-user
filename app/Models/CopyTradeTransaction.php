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

    public function tradingAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function master(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id', 'id');
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
