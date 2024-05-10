<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VerifyOtp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'mobile',
        'otp',
        'created_at',
    ];


}
