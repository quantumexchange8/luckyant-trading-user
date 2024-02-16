<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'trading_account_id',
        'status',
        'approval_date',
        'remarks',
        'handle_by',
    ];

    protected $casts = [
        'approval_date' => 'date',
    ];
}
