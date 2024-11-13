<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGateway extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'platform',
        'environment',
        'payment_url',
        'payment_app_name',
        'secret_key',
        'secondary_key',
        'edited_by',
        'status',
    ];
}
