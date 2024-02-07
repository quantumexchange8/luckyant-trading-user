<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category',
        'transaction_type',
        'from_wallet_id',
        'to_wallet_id',
        'from_meta_login',
        'to_meta_login',
        'ticket',
        'transaction_number',
        'to_wallet_address',
        'txn_hash',
        'amount',
        'transaction_charges',
        'transaction_amount',
        'new_wallet_amount',
        'status',
        'comment',
        'remarks',
        'handle_by',
    ];
}
