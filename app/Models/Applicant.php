<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
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
        'ticket_type',
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

    // Relations
    public function application_form(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class, 'application_form_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function transport_detail(): HasOne
    {
        return $this->hasOne(ApplicantTransport::class, 'applicant_id', 'id');
    }
}
