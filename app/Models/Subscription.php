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
        'meta_balance',
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
}
