<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'status',
        'handle_by',
    ];

    // Relations
    public function leaders(): HasMany
    {
        return $this->hasMany(ApplicationFormToLeader::class, 'application_form_id', 'id');
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class, 'application_form_id', 'id')->where('user_id', auth()->id());
    }
}
