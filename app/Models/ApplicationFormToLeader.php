<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationFormToLeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_form_id',
        'user_id',
    ];
}
