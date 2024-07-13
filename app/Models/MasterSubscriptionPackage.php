<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterSubscriptionPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'master_id',
        'meta_login',
        'label',
        'amount',
        'max_out_amount',
    ];
}
