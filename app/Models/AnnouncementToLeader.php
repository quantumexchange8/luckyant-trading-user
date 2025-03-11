<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnouncementToLeader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'announcement_id',
        'user_id',
    ];
}
