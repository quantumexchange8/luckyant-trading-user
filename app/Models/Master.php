<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Master extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'meta_login',
        'category',
        'type',
        'min_join_equity',
        'sharing_profit',
        'market_profit',
        'company_profit',
        'estimated_monthly_returns',
        'estimated_lot_size',
        'subscription_fee',
        'total_fund',
        'extra_fund',
        'roi_period',
        'join_period',
        'signal_status',
        'is_public',
        'status',
        'total_subscribers',
        'max_drawdown',
        'management_fee',
    ];

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class, 'master_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tradingUser(): HasOne
    {
        return $this->hasOne(TradingUser::class, 'meta_login', 'meta_login');
    }

    public function copyTradeHistories(): HasMany
    {
        return $this->hasMany(CopyTradeHistory::class, 'meta_login', 'meta_login');
    }

    public function tradeHistories(): HasMany
    {
        return $this->hasMany(TradeHistory::class, 'meta_login', 'meta_login');
    }

    public function masterManagementFee(): HasMany
    {
        return $this->hasMany(MasterManagementFee::class, 'master_id', 'id');
    }

    public function activeCapitalFund(): HasMany
    {
        return $this->hasMany(PammSubscription::class, 'master_id', 'id')->where('status', 'Active')->withTrashed();
    }

    public function visible_to_leaders(): HasMany
    {
        return $this->hasMany(MasterToLeader::class, 'master_id', 'id');
    }

    public function active_copy_trades(): HasMany
    {
        return $this->hasMany(Subscriber::class, 'master_id', 'id')
            ->where('status', 'Subscribing');
    }

    public function active_pamm(): HasMany
    {
        return $this->hasMany(PammSubscription::class, 'master_id', 'id')
            ->where('status', 'Active');
    }
}
