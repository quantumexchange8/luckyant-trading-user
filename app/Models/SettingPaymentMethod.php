<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SettingPaymentMethod extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'payment_method',
        'payment_account_name',
        'payment_platform_name',
        'account_no',
        'bank_swift_code',
        'bank_code',
        'bank_code_type',
        'country',
        'currency',
        'status',
        'handle_by'
    ];

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }
}
