<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SwitchMaster extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'meta_login',
        'old_master_id',
        'old_master_meta_login',
        'new_master_id',
        'new_master_meta_login',
        'old_subscriber_id',
        'approval_date',
        'status',
        'remarks',
        'handle_by',
    ];

    protected $casts = [
        'approval_date' => 'timestamp',
    ];
}
