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
        'subscription_id',
        'subscription_number',
        'subscription_period',
        'transaction_id',
        'subscription_fee',
        'settlement_start_date',
        'settlement_date',
        'termination_date',
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

    public function tradingAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function master(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'master_meta_login', 'meta_login');
    }
}
