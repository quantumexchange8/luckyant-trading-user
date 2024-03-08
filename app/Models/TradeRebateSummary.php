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
    
}
