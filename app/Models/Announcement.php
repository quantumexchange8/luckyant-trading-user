<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Announcement extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'subject',
        'details',
        'type',
    ];

    public function leaders(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, AnnouncementToLeader::class, 'announcement_id', 'id', 'id', 'user_id');
    }
}
