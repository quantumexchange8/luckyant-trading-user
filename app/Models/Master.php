<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function tradingAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'trading_account_id', 'id');
    }

    public function subscribers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscriber::class, 'master_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TradingUser::class, 'id', 'trading_account_id');
    }

    public function copyTradeHistories(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(CopyTradeHistory::class, 'meta_login', 'meta_login');
    }

    public function tradeHistories(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(TradeHistory::class, 'meta_login', 'meta_login');
    }

    public function masterManagementFee(): \Illuminate\Database\Eloquent\Relations\hasMany
    {
        return $this->hasMany(MasterManagementFee::class, 'master_id', 'id');
    }

}
