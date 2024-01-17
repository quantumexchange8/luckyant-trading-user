<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'user_id',
        'from_trading_acc_no',
        'to_trading_acc_no',
        'ticket',
        'transaction_id',
        'category',
        'type',
        'amount',
        'currency',
        'payment_charges',
        'account_type',
        'account_no',
        'approval_date',
        'remarks',
        'handle_by',
    ];

    protected $casts = [
        'approval_date' => 'date',
    ];
}
