<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantTransport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_form_id',
        'applicant_id',
        'user_id',
        'type',
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
