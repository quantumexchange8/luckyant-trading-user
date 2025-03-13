<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationTransport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_form_id',
        'application_candidate_id',
        'user_id',
        'name',
        'gender',
        'country_id',
        'dob',
        'phone_number',
        'identity_number',
        'departure_address',
        'return_address',
    ];
}
