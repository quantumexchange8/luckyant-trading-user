<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PammSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'meta_login',
        'master_id',
        'master_meta_login',
        'subscription_amount',
        'subscription_package_id',
        'subscription_package_product',
        'type',
        'strategy_type',
        'transaction_id',
        'subscription_number',
        'subscription_period',
        'settlement_period',
        'subscription_fee',
        'settlement_date',
        'expired_date',
        'termination_date',
        'max_out_amount',
        'approval_date',
        'status',
        'remarks',
        'extra_conditions',
        'delivery_address',
        'handle_by',
    ];

    protected $casts = [
        'settlement_date' => 'datetime',
        'expired_date' => 'datetime',
        'approval_date' => 'datetime',
        'termination_date' => 'datetime',
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

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function package(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MasterSubscriptionPackage::class, 'subscription_package_id', 'id');
    }

    public function masterManagementFee(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(MasterManagementFee::class, 'master_id', 'id');
    }
}
