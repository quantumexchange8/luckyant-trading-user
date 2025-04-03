<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTerms extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'master_id',
        'term_contents',
    ];
}
