<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TradeRebateSummary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'upline_user_id',
        'upline_meta_login',
        'upline_meta_type',
        'user_id',
        'meta_login',
        'meta_type',
        'symbol_group',
        'closed_time',
        'volume',
        'rebate',
        'status',
        'execute_at',
    ];
    
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_user_id', 'id');
    }

    public function ofUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function symbolGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SymbolGroup::class, 'symbol_group', 'id');
    }

    public function tradingAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'meta_login', 'meta_login');
    }

}
