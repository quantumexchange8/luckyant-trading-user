<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingSettlementPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'label',
        'value',
        'status',
        'updated_by',
    ];
}
