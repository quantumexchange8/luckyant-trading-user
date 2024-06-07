<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterLeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'master_id',
        'leader_id',
    ];
}
