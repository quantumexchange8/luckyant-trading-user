<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'setting_id',
        'user_id',
        'setting_old_value',
        'setting_new_value',
    ];
}
