<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterManagementFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'master_id',
        'meta_login',
        'penalty_days',
        'penalty_percentage',
    ];
}
