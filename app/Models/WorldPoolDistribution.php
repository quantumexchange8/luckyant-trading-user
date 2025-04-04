<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorldPoolDistribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'approved_at'
    ];
}
