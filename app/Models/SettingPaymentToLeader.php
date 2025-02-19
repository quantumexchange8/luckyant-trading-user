<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingPaymentToLeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'setting_payment_method_id',
        'user_id',
    ];
}
