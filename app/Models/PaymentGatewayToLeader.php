<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGatewayToLeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_gateway_id',
        'user_id',
    ];

    // Relations
    public function payment_gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id', 'id');
    }
}
