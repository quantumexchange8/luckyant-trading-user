<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function accountType(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AccountType::class, 'group', 'id');
    }
}
