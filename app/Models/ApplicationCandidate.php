<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationCandidate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_form_id',
        'user_id',
        'name',
        'gender',
        'country_id',
        'email',
        'phone_number',
        'identity_number',
        'requires_transport',
        'requires_accommodation',
        'requires_ib_training',
        'status',
    ];

    protected $casts = [
        'requires_transport' => 'boolean',
        'requires_accommodation' => 'boolean',
        'requires_ib_training' => 'boolean',
    ];
}
