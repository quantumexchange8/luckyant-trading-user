<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTypeLeverage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_type_id',
        'setting_leverage_id',
    ];

    // Relations
    public function leverage(): BelongsTo
    {
        return $this->belongsTo(SettingLeverage::class, 'setting_leverage_id', 'id');
    }
}
