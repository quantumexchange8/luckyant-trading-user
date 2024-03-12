<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CopyTradeHistory extends Model
{
    protected $fillable = [
        'user_id',
        'meta_login',
        'symbol',
        'ticket',
        'time_open',
        'trade_type',
        'volume',
        'price_open',
        'time_close',
        'price_close',
        'closed_profit',
        'status',
        'master_id',
        'master_ticket_id',
        'user_type',
    ];
}
